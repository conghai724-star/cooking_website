import sys

def dump_hex(file_path):
    try:
        with open(file_path, 'rb') as f:
            data = f.read(1000)
            print(f"Hex dump of {file_path}:")
            print(data.hex(' ', 1))
    except Exception as e:
        print(f"Error: {e}")

if len(sys.argv) > 1:
    dump_hex(sys.argv[1])
