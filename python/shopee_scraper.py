# import sys
# import json
# import time
# import urllib.parse
# import re
# from selenium import webdriver
# from selenium.webdriver.chrome.options import Options
# from selenium.webdriver.common.by import By
# from selenium.webdriver.support.ui import WebDriverWait
# from selenium.webdriver.support import expected_conditions as EC

# def generate_shopee_url(judul, shop_id, item_id):
#     slug = judul.lower()
#     slug = re.sub(r'[^a-z0-9\s-]', '', slug)
#     slug = re.sub(r'[\s-]+', '-', slug).strip('-')
#     return f"https://shopee.co.id/{slug}-i.{shop_id}.{item_id}"

# def run_scraping_process(keyword, limit_target):
#     # Hubungkan Selenium ke Chrome yang sudah berjalan di port 9222
#     options = Options()
#     options.add_experimental_option("debuggerAddress", "127.0.0.1:9222")
    
#     try:
#         driver = webdriver.Chrome(options=options)
#     except Exception as e:
#         print(json.dumps({"error": f"Gagal terhubung ke Chrome: {str(e)}"}))
#         sys.exit(1)

#     if "shopee.co.id" not in driver.current_url or "affiliate.shopee.co.id" in driver.current_url:
#         driver.get("https://shopee.co.id")
#         time.sleep(2)

#     extra_params_encoded = urllib.parse.quote(json.dumps({
#         "global_search_session_id": "gs-869174e1-79ee-46b1-9ab2-1b447242ca0c",
#         "search_session_id": "ss-22ba877e-a1bf-45ab-8a84-b5c317dc79f2"
#     }))

#     search_api_url = (
#         f"https://shopee.co.id/api/v4/search/search_items?"
#         f"by=relevancy&extra_params={extra_params_encoded}&keyword={keyword}&"
#         f"limit={limit_target}&newest=0&order=desc&page_type=search&"
#         f"scenario=PAGE_GLOBAL_SEARCH&source=SRP&version=2"
#     )

#     # 1. Fetch Search API
#     js_search = f'return fetch("{search_api_url}").then(r => r.json()).then(d => JSON.stringify(d));'
#     raw_search = driver.execute_script(js_search)
#     search_json = json.loads(raw_search)
#     items = search_json.get("items", [])

#     if not items:
#         print(json.dumps([]))
#         return

#     raw_products = []
#     for item in items:
#         base = item.get("item_basic") if item.get("item_basic") else item
#         item_id = base.get("itemid")
#         shop_id = base.get("shopid")
#         judul_awal = base.get("name")
#         if item_id and shop_id:
#             raw_products.append({"item_id": item_id, "shop_id": shop_id, "judul_awal": judul_awal})

#     # 2. Parallel Fetch Detail Produk & Toko
#     js_parallel_fetch = """
#     const products = arguments[0];
#     const promises = products.map(async (p) => {
#         try {
#             const detailUrl = `https://shopee.co.id/api/v4/item/get?itemid=${p.item_id}&shopid=${p.shop_id}`;
#             const detailRes = await fetch(detailUrl).then(r => r.json());
#             const data = detailRes.data || {};
            
#             const shopUrl = `https://shopee.co.id/api/v4/product/get_shop_info?shopid=${p.shop_id}`;
#             const shopRes = await fetch(shopUrl).then(r => r.json());
#             const shopData = shopRes.data || {};
            
#             let totalShopReviews = 0;
#             if (shopData.rating_good !== undefined) {
#                 totalShopReviews = (shopData.rating_good || 0) + (shopData.rating_normal || 0) + (shopData.rating_bad || 0);
#             } else if (shopData.response_count !== undefined) {
#                 totalShopReviews = shopData.response_count;
#             }

#             return {
#                 item_id: p.item_id,
#                 shop_id: p.shop_id,
#                 judul: data.name || p.judul_awal,
#                 harga: data.price ? Math.floor(data.price / 100000) : 0,
#                 rating_produk: data.item_rating ? Number((data.item_rating.rating_star || 0).toFixed(1)) : 0.0,
#                 total_ulasan_produk: data.item_rating && data.item_rating.rating_count ? data.item_rating.rating_count[0] : 0,
#                 toko: shopData.name || "Toko Tidak Diketahui",
#                 rating_toko: shopData.rating_star ? Number((shopData.rating_star || 0).toFixed(1)) : 0.0,
#                 total_ulasan_toko: totalShopReviews
#             };
#         } catch (e) {
#             return null;
#         }
#     });
#     return Promise.all(promises).then(results => JSON.stringify(results.filter(r => r !== null)));
#     """

#     raw_results = driver.execute_script(js_parallel_fetch, raw_products)
#     detailed_products = json.loads(raw_results)

#     daftar_produk = []
#     for dp in detailed_products:
#         dp['url_asli'] = generate_shopee_url(dp['judul'], dp['shop_id'], dp['item_id'])
#         dp['url_afiliasi'] = None
#         daftar_produk.append(dp)

#     # 3. Konversi Link Afiliasi
#     driver.get("https://affiliate.shopee.co.id/offer/custom_link")
#     time.sleep(3)

#     mass_input_text = "\n".join([prod['url_asli'] for prod in daftar_produk])

#     try:
#         input_box = WebDriverWait(driver, 10).until(
#             EC.element_to_be_clickable((By.XPATH, '//textarea | //input[@type="text"]'))
#         )
#         input_box.clear()
#         input_box.send_keys(mass_input_text)
#         time.sleep(1)

#         btn_convert = WebDriverWait(driver, 10).until(
#             EC.element_to_be_clickable((By.XPATH, '//button[contains(., "Link") or contains(., "Dapatkan")]'))
#         )
#         btn_convert.click()
#         time.sleep(4)

#         link_elem = WebDriverWait(driver, 10).until(
#             EC.presence_of_element_located((By.XPATH, '//textarea[contains(., "s.shopee.co.id")] | //input[contains(@value, "s.shopee.co.id")]'))
#         )
        
#         raw_text = link_elem.get_attribute('value') or link_elem.text
#         extracted_links = [line.strip() for line in raw_text.split('\n') if line.strip()]

#         for idx, prod in enumerate(daftar_produk):
#             if idx < len(extracted_links):
#                 match = re.search(r'(https://s\.shopee\.co\.id/[A-Za-z0-9_-]+)', extracted_links[idx])
#                 prod['url_afiliasi'] = match.group(1) if match else extracted_links[idx]

#     except Exception:
#         pass

#     # Cetak JSON murni ke STDOUT agar Laravel bisa menangkap outputnya
#     print(json.dumps(daftar_produk, ensure_ascii=False))

# if __name__ == "__main__":
#     # Mengambil argumen keyword dan limit dari command line
#     keyword_input = sys.argv[1] if len(sys.argv) > 1 else "ram sodimm"
#     limit_input = int(sys.argv[2]) if len(sys.argv) > 2 else 5
    
#     run_scraping_process(keyword_input, limit_input)
import sys
import json
import time
import urllib.parse
import re
from selenium import webdriver
from selenium.webdriver.chrome.options import Options
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC

def generate_shopee_url(judul, shop_id, item_id):
    slug = judul.lower()
    slug = re.sub(r'[^a-z0-9\s-]', '', slug)
    slug = re.sub(r'[\s-]+', '-', slug).strip('-')
    return f"https://shopee.co.id/{slug}-i.{shop_id}.{item_id}"

def run_scraping_process(keyword, limit_target):
    options = Options()
    options.add_experimental_option("debuggerAddress", "127.0.0.1:9222")
    
    try:
        driver = webdriver.Chrome(options=options)
    except Exception as e:
        print(json.dumps([]))
        return

    try:
        # FIX PERTAMA: Selalu pastikan tab kembali ke shopee.co.id utama (Bukan Affiliate)
        current_url = driver.current_url.lower()
        if "affiliate.shopee.co.id" in current_url or "shopee.co.id" not in current_url:
            driver.get("https://shopee.co.id")
            time.sleep(2)

        # 1. Fetch API Search
        extra_params_encoded = urllib.parse.quote(json.dumps({
            "global_search_session_id": "gs-869174e1-79ee-46b1-9ab2-1b447242ca0c",
            "search_session_id": "ss-22ba877e-a1bf-45ab-8a84-b5c317dc79f2"
        }))

        search_api_url = (
            f"https://shopee.co.id/api/v4/search/search_items?"
            f"by=relevancy&extra_params={extra_params_encoded}&keyword={urllib.parse.quote(keyword)}&"
            f"limit={limit_target}&newest=0&order=desc&page_type=search&"
            f"scenario=PAGE_GLOBAL_SEARCH&source=SRP&version=2"
        )

        js_search = f'return fetch("{search_api_url}").then(r => r.json()).then(d => JSON.stringify(d));'
        raw_search = driver.execute_script(js_search)
        
        if not raw_search:
            print(json.dumps([]))
            return

        search_json = json.loads(raw_search)
        items = search_json.get("items", [])

        if not items:
            print(json.dumps([]))
            return

        raw_products = []
        for item in items:
            base = item.get("item_basic") if item.get("item_basic") else item
            item_id = base.get("itemid")
            shop_id = base.get("shopid")
            judul_awal = base.get("name")
            if item_id and shop_id:
                raw_products.append({"item_id": item_id, "shop_id": shop_id, "judul_awal": judul_awal})

        # 2. Fetch Detail Produk
        js_parallel_fetch = """
        const products = arguments[0];
        const promises = products.map(async (p) => {
            try {
                const detailUrl = `https://shopee.co.id/api/v4/item/get?itemid=${p.item_id}&shopid=${p.shop_id}`;
                const detailRes = await fetch(detailUrl).then(r => r.json());
                const data = detailRes.data || {};
                
                const shopUrl = `https://shopee.co.id/api/v4/product/get_shop_info?shopid=${p.shop_id}`;
                const shopRes = await fetch(shopUrl).then(r => r.json());
                const shopData = shopRes.data || {};
                
                let totalShopReviews = 0;
                if (shopData.rating_good !== undefined) {
                    totalShopReviews = (shopData.rating_good || 0) + (shopData.rating_normal || 0) + (shopData.rating_bad || 0);
                } else if (shopData.response_count !== undefined) {
                    totalShopReviews = shopData.response_count;
                }

                return {
                    item_id: p.item_id,
                    shop_id: p.shop_id,
                    judul: data.name || p.judul_awal,
                    harga: data.price ? Math.floor(data.price / 100000) : 0,
                    rating_produk: data.item_rating ? Number((data.item_rating.rating_star || 0).toFixed(1)) : 0.0,
                    total_ulasan_produk: data.item_rating && data.item_rating.rating_count ? data.item_rating.rating_count[0] : 0,
                    toko: shopData.name || "Toko Tidak Diketahui",
                    rating_toko: shopData.rating_star ? Number((shopData.rating_star || 0).toFixed(1)) : 0.0,
                    total_ulasan_toko: totalShopReviews
                };
            } catch (e) {
                return null;
            }
        });
        return Promise.all(promises).then(results => JSON.stringify(results.filter(r => r !== null)));
        """

        raw_results = driver.execute_script(js_parallel_fetch, raw_products)
        detailed_products = json.loads(raw_results) if raw_results else []

        daftar_produk = []
        for dp in detailed_products:
            dp['url_asli'] = generate_shopee_url(dp['judul'], dp['shop_id'], dp['item_id'])
            dp['url_afiliasi'] = None
            daftar_produk.append(dp)

        # 3. Konversi Link Afiliasi
        if daftar_produk:
            try:
                driver.get("https://affiliate.shopee.co.id/offer/custom_link")
                time.sleep(3)

                mass_input_text = "\n".join([prod['url_asli'] for prod in daftar_produk])

                input_box = WebDriverWait(driver, 5).until(
                    EC.element_to_be_clickable((By.XPATH, '//textarea | //input[@type="text"]'))
                )
                input_box.clear()
                input_box.send_keys(mass_input_text)
                time.sleep(1)

                btn_convert = WebDriverWait(driver, 5).until(
                    EC.element_to_be_clickable((By.XPATH, '//button[contains(., "Link") or contains(., "Dapatkan")]'))
                )
                btn_convert.click()
                time.sleep(3)

                link_elem = WebDriverWait(driver, 5).until(
                    EC.presence_of_element_located((By.XPATH, '//textarea[contains(., "s.shopee.co.id")] | //input[contains(@value, "s.shopee.co.id")]'))
                )
                
                raw_text = link_elem.get_attribute('value') or link_elem.text
                extracted_links = [line.strip() for line in raw_text.split('\n') if line.strip()]

                for idx, prod in enumerate(daftar_produk):
                    if idx < len(extracted_links):
                        match = re.search(r'(https://s\.shopee\.co\.id/[A-Za-z0-9_-]+)', extracted_links[idx])
                        prod['url_afiliasi'] = match.group(1) if match else extracted_links[idx]
            except Exception:
                pass

        # FIX KEDUA: Kembalikan halaman ke shopee.co.id sebelum exit agar tab siap untuk job berikutnya
        try:
            driver.get("https://shopee.co.id")
        except Exception:
            pass

        print(json.dumps(daftar_produk, ensure_ascii=False))

    except Exception:
        print(json.dumps([]))

if __name__ == "__main__":
    keyword_input = sys.argv[1] if len(sys.argv) > 1 else "ram ddr4"
    limit_input = int(sys.argv[2]) if len(sys.argv) > 2 else 3
    
    run_scraping_process(keyword_input, limit_input)