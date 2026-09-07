import json
import sys
import time
from selenium import webdriver
from selenium.webdriver.chrome.options import Options


def check_shopee_session():
    options = Options()
    options.add_experimental_option("debuggerAddress", "127.0.0.1:9222")

    status = {
        "chrome_connected": False,
        "shopee_logged_in": False,
        "affiliate_logged_in": False,
        "session_state": "disconnected",
        "status_message": "",
        "message": "",
    }

    try:
        driver = webdriver.Chrome(options=options)

        if not driver.window_handles:
            raise Exception("Tidak ada jendela Chrome yang aktif.")

        driver.switch_to.window(driver.window_handles[0])
        status["chrome_connected"] = True

    except Exception as e:
        status["session_state"] = "disconnected"
        status["status_message"] = (
            "Chrome Port 9222 terputus atau browser telah ditutup."
        )
        status["message"] = status["status_message"]
        print(json.dumps(status))
        sys.exit(0)

    try:
        # 1. Cek Sesi Main Shopee
        driver.get("https://shopee.co.id")
        time.sleep(2)

        cookies = {c["name"]: c["value"] for c in driver.get_cookies()}
        if "SPC_EC" in cookies or "SPC_U" in cookies:
            status["shopee_logged_in"] = True

        # 2. Cek Sesi Dashboard Afiliasi
        driver.get("https://affiliate.shopee.co.id/offer/custom_link")
        time.sleep(2)

        current_url = driver.current_url
        if "login" not in current_url and "custom_link" in current_url:
            status["affiliate_logged_in"] = True

        # Tentukan status akhir
        if status["shopee_logged_in"] and status["affiliate_logged_in"]:
            status["session_state"] = "ready"
            status["status_message"] = (
                "Sesi login Shopee & Afiliasi SIAP digunakan."
            )
        elif status["shopee_logged_in"] and not status["affiliate_logged_in"]:
            status["session_state"] = "need_login"
            status["status_message"] = (
                "Shopee login, tetapi sesi Afiliasi belum siap/butuh login ulang."
            )
        else:
            status["session_state"] = "need_login"
            status["status_message"] = (
                "Sesi Shopee belum login. Silakan login manual di browser Chrome 9222."
            )

        status["message"] = status["status_message"]

    except Exception as e:
        status["session_state"] = "disconnected"
        status["status_message"] = (
            "Koneksi ke browser terputus saat melakukan pengecekan."
        )
        status["message"] = status["status_message"]

    print(json.dumps(status))


if __name__ == "__main__":
    check_shopee_session()