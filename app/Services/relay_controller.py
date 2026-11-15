"""USB HID relay controller.

Called from PHP (RelayService::control) as:
    py relay_controller.py <channel> <on|off>

Diagnostics when the hardware arrives:
    py relay_controller.py --scan    # list HID devices, pick VID/PID here
    py relay_controller.py --sniff   # dump reports while clicking vendor software

Environment overrides (optional):
    RELAY_VID                   e.g. 0x16c0  (skip "relay" name matching)
    RELAY_PID                   e.g. 0x05df
    RELAY_CHANNELS_PER_DEVICE   relays per board, default 2
    RELAY_PROTOCOL              usbrelay (default) | a0
"""

import os
import sys
import time

DEFAULT_VID = 0x16C0
DEFAULT_PID = 0x05DF
REPORT_SIZE = 8
TIMEOUT_MS = 2000

# Report layouts. Byte 0 is the HID report id (unused on these boards).
PROTOCOLS = {
    # dcttech / generic USBRelay: 0xFF on, 0xFD off.
    "usbrelay": lambda relay, on: [0x00, 0xFF if on else 0xFD, relay, 0, 0, 0, 0, 0],
    # "USB Relay Manager" (QAMZ) boards. Unverified guess - confirm with --sniff.
    "a0": lambda relay, on: [0x00, 0xA0, relay, 0x01 if on else 0x00],
}


def _hid():
    try:
        import hid
    except ImportError:
        sys.exit("ERROR: hidapi not installed. Run: pip install hidapi")
    return hid


def _env_int(name):
    raw = os.environ.get(name, "").strip()
    if not raw:
        return None
    try:
        return int(raw, 0)
    except ValueError:
        sys.exit("ERROR: %s must be an integer, got %r" % (name, raw))


def find_devices():
    """Return HID devices that look like relay boards, in stable order."""
    hid = _hid()
    vid = _env_int("RELAY_VID")
    pid = _env_int("RELAY_PID")

    devices = hid.enumerate()

    if vid is not None:
        return [
            d for d in devices
            if d["vendor_id"] == vid and (pid is None or d["product_id"] == pid)
        ]

    # No explicit VID: match by name, then fall back to the default VID/PID.
    named = [
        d for d in devices
        if "relay" in ("%s %s" % (
            d.get("product_string") or "", d.get("manufacturer_string") or ""
        )).lower()
    ]
    if named:
        return named

    return [d for d in devices if d["vendor_id"] == DEFAULT_VID and d["product_id"] == DEFAULT_PID]


def open_board(index):
    hid = _hid()
    devices = find_devices()
    if not devices:
        sys.exit(
            "ERROR: no relay board found. Plug the board in and run --scan, "
            "then set RELAY_VID / RELAY_PID."
        )
    if index >= len(devices):
        sys.exit(
            "ERROR: board #%d not found (%d detected). Check RELAY_CHANNELS_PER_DEVICE "
            "and the USB port mapping." % (index + 1, len(devices))
        )

    device = hid.device()
    device.open_path(devices[index]["path"])
    return device


def send_relay_command(channel, state):
    """Map a logical channel (1..n, as stored in tables.relay_channel) to a relay."""
    per_board = _env_int("RELAY_CHANNELS_PER_DEVICE") or 2
    protocol = os.environ.get("RELAY_PROTOCOL", "usbrelay").strip()
    if protocol not in PROTOCOLS:
        sys.exit("ERROR: unknown RELAY_PROTOCOL %r (use: %s)" % (protocol, ", ".join(PROTOCOLS)))

    board_index = (channel - 1) // per_board
    relay = (channel - 1) % per_board + 1
    report = PROTOCOLS[protocol](relay, state == "on")

    device = open_board(board_index)
    try:
        written = device.write(report)
        if not written or written < 0:
            sys.exit("ERROR: relay write failed (report %s)" % (report,))
    finally:
        device.close()

    print("SUCCESS: Relay %d turned %s" % (channel, state.upper()))


def scan():
    hid = _hid()
    devices = hid.enumerate()
    if not devices:
        print("No HID devices found.")
        return

    print("%-6s %-6s %-28s %-12s %s" % ("VID", "PID", "PRODUCT", "SERIAL", "PATH"))
    for d in devices:
        print("%-6s %-6s %-28s %-12s %s" % (
            hex(d["vendor_id"]),
            hex(d["product_id"]),
            (d.get("product_string") or "-")[:28],
            (d.get("serial_number") or "-")[:12],
            d["path"].decode(errors="replace") if isinstance(d["path"], bytes) else d["path"],
        ))

    matches = find_devices()
    print("\n%d device(s) match the relay filter." % len(matches))
    for i, d in enumerate(matches):
        print("  board #%d -> %s %s (%s)" % (
            i + 1,
            hex(d["vendor_id"]),
            hex(d["product_id"]),
            d.get("serial_number") or "no serial",
        ))


def sniff():
    """Dump input reports so the real protocol can be read off the vendor tool."""
    device = open_board(0)
    device.set_nonblocking(1)
    print("Listening for HID reports for 30s. Click Open/Close in the vendor software.")
    deadline = time.time() + 30
    try:
        while time.time() < deadline:
            data = device.read(64)
            if data:
                print("RX", " ".join("%02X" % b for b in data))
            else:
                time.sleep(0.05)
    except KeyboardInterrupt:
        pass
    finally:
        device.close()


def main(argv):
    if "--scan" in argv:
        scan()
        return
    if "--sniff" in argv:
        sniff()
        return

    if len(argv) < 3:
        print("Usage: python relay_controller.py <channel_number> <on/off>")
        print("       python relay_controller.py --scan | --sniff")
        sys.exit(1)

    try:
        channel = int(argv[1])
    except ValueError:
        sys.exit("ERROR: Channel must be an integer")

    state = argv[2].lower()
    if state not in ("on", "off"):
        sys.exit("ERROR: State must be 'on' or 'off'")
    if channel < 1:
        sys.exit("ERROR: Channel must be >= 1")

    send_relay_command(channel, state)


if __name__ == "__main__":
    main(sys.argv)
