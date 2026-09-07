import os
import subprocess
import time


def launch_chrome_for_login():
    """Menyalakan Google Chrome asli untuk keperluan login manual & session scraping."""
    base_dir = os.path.dirname(os.path.abspath(__file__))
    profile_dir = os.path.join(base_dir, "shopee_profile")

    # Jalankan perintah persis seperti di terminal menggunakan string tunggal
    cmd = f'/usr/bin/google-chrome --remote-debugging-port=9222 --user-data-dir="{profile_dir}"'

    print("[LOGIN] Menyalakan Google Chrome di port 9222...")
    subprocess.Popen(
        cmd, shell=True, stdout=subprocess.DEVNULL, stderr=subprocess.DEVNULL
    )
    time.sleep(3)


if __name__ == "__main__":
    launch_chrome_for_login()