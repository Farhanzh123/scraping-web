import sys
import json
import socket
from selenium import webdriver
from selenium.webdriver.chrome.options import Options

def check_port_open(host="127.0.0.1", port=9222, timeout=3):
    try:
        sock = socket.create_connection((host, port), timeout=timeout)
        sock.close()
        return True
    except Exception:
        return False

def export_cookies():
    if not check_port_open():
        print(json.dumps({"error": "Chrome Port 9222 tidak aktif. Silakan buka Chrome debugging terlebih dahulu."}))
        sys.exit(1)

    options = Options()
    options.add_experimental_option("debuggerAddress", "127.0.0.1:9222")
    
    try:
        driver = webdriver.Chrome(options=options)
        cookies = driver.get_cookies()

        if not cookies:
            print(json.dumps({"error": "Tidak ada cookie ditemukan. Pastikan sudah membuka Shopee di Chrome 9222."}))
            sys.exit(1)

        print(json.dumps(cookies, ensure_ascii=False))
    except Exception as e:
        print(json.dumps({"error": f"Gagal mengambil cookie: {str(e)}"}))
        sys.exit(1)

if __name__ == "__main__":
    export_cookies()