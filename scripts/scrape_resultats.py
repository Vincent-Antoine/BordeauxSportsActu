import os
import re
import json
import time
import requests
import xml.etree.ElementTree as ET
from datetime import datetime
from email.utils import parsedate_to_datetime
from bs4 import BeautifulSoup
from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.chrome.service import Service
# Décommentez si vous souhaitez toujours utiliser ChromeDriverManager
# from webdriver_manager.chrome import ChromeDriverManager
from selenium.webdriver.chrome.options import Options
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC


###############################################################################
#                        FONCTION RSS FOOTBALL                                #
###############################################################################

def scrape_rss_football():
    rss_url = "https://www.matchendirect.fr/rss/foot-bordeaux-e891.xml"
    headers = {
        "User-Agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64)"
    }
    results = []

    try:
        response = requests.get(rss_url, headers=headers, timeout=10)
        response.raise_for_status()
        root = ET.fromstring(response.content)

        for item in root.findall(".//item"):
            title_element = item.find("title")
            pub_date_element = item.find("pubDate")
            if title_element is None or pub_date_element is None:
                continue

            title = title_element.text.strip() if title_element.text else ""
            pub_date = pub_date_element.text.strip() if pub_date_element.text else ""

            if "score final" in title.lower():
                try:
                    dt = parsedate_to_datetime(pub_date)
                    match_date = dt.strftime("%d.%m.%Y")
                except Exception:
                    continue

                match_info = re.search(
                    r"^(.*?)\s*:\s*(.*?)\s*-\s*(.*?)\s*\(score final\s*:\s*(\d+)-(\d+)\)",
                    title,
                    re.IGNORECASE
                )
                if match_info:
                    home_team = match_info.group(2).strip()
                    away_team = match_info.group(3).strip()
                    home_score = match_info.group(4).strip()
                    away_score = match_info.group(5).strip()

                    results.append({
                        "date_heure": match_date,
                        "home_team": home_team,
                        "away_team": away_team,
                        "home_score": home_score,
                        "away_score": away_score
                    })

    except requests.RequestException as e:
        print(f"Erreur RSS football : {e}")

    return results


###############################################################################
#               SCRAPER FLASHSCORE (UTILISÉ POUR RUGBY, RUGBY_F, HOCKEY)      #
###############################################################################

def parse_flashscore_page(driver, url):
    """
    Ouvre une URL FlashScore dans un même driver,
    attend que les matchs soient présents, parse et renvoie la liste de résultats.
    """
    results = []
    try:
        driver.get(url)
        # On réduit le temps d'attente en se fiant à WebDriverWait
        WebDriverWait(driver, 10).until(
            EC.presence_of_all_elements_located((By.CLASS_NAME, "event__match"))
        )

        # Si vous constatez que certains éléments mettent un peu plus de temps,
        # vous pouvez ajouter un petit sleep de 1 ou 2 secondes max.
        time.sleep(1)

        soup = BeautifulSoup(driver.page_source, 'lxml')
        match_divs = soup.find_all(class_="event__match")

        for match in match_divs:
            try:
                match_time_el = match.find(class_="event__time")
                home_participant_el = match.find(class_="event__participant--home")
                away_participant_el = match.find(class_="event__participant--away")
                home_score_el = match.find(class_="event__score--home")
                away_score_el = match.find(class_="event__score--away")

                if all([match_time_el, home_participant_el, away_participant_el, home_score_el, away_score_el]):
                    results.append({
                        "date_heure": match_time_el.get_text(strip=True),
                        "home_team": home_participant_el.get_text(strip=True),
                        "away_team": away_participant_el.get_text(strip=True),
                        "home_score": home_score_el.get_text(strip=True),
                        "away_score": away_score_el.get_text(strip=True)
                    })
            except Exception:
                continue

    except Exception as e:
        print(f"Erreur lors du parsing de {url} : {e}")

    return results


def scrape_flashscore_for_sports(flashscore_urls):
    """
    Lance un *seul* navigateur Selenium pour tous les sports passés dans `flashscore_urls`.
    flashscore_urls est une liste de tuples (sport_key, url).
    Retourne un dict { "sport": [ ...results... ], ... }.
    """
    # Options Chrome
    options = Options()
    options.add_argument("--headless")
    options.add_argument("--disable-gpu")
    options.add_argument("--no-sandbox")
    options.add_argument("--disable-dev-shm-usage")
    options.add_argument("--disable-blink-features=AutomationControlled")
    options.add_argument("--window-size=1920,1080")

    # Si vous avez déjà installé votre driver, utilisez simplement :
    driver = webdriver.Chrome(options=options)
    # Si vous voulez garder webdriver_manager, décommentez :
    # driver = webdriver.Chrome(service=Service(ChromeDriverManager().install()), options=options)

    results_dict = {}

    try:
        for sport_key, url in flashscore_urls:
            parsed_data = parse_flashscore_page(driver, url)
            # On limite à 20 avant de stocker
            results_dict[sport_key] = parsed_data[:20]
    finally:
        driver.quit()

    return results_dict


###############################################################################
#               SCRAPER JSA-BMB.FR (BASKET)                                    #
###############################################################################

def scrape_jsa_bmb(url):
    headers = {"User-Agent": "Mozilla/5.0"}
    results = []

    try:
        response = requests.get(url, headers=headers, timeout=10)
        response.raise_for_status()
        soup = BeautifulSoup(response.text, 'lxml')

        match_divs = soup.find_all(class_="widget-results__item")
        for match in match_divs:
            try:
                date_time_el = match.find("time")
                home_team_el = match.find(class_="widget-results__team--odd")
                away_team_el = match.find(class_="widget-results__team--even")
                home_score_el = match.find(class_="widget-results__score-winner")
                away_score_el = match.find(class_="widget-results__score-loser")

                if all([date_time_el, home_team_el, away_team_el, home_score_el, away_score_el]):
                    results.append({
                        "date_heure": date_time_el.get_text(strip=True),
                        "home_team": home_team_el.get_text(strip=True),
                        "away_team": away_team_el.get_text(strip=True),
                        "home_score": home_score_el.get_text(strip=True),
                        "away_score": away_score_el.get_text(strip=True)
                    })
            except Exception:
                continue

    except requests.RequestException as e:
        print(f"Erreur Basket (JSA-BMB) : {e}")

    return results


###############################################################################
#                               MAIN                                           #
###############################################################################

def main():
    # URLs à scraper
    urls = {
        "football": "https://www.flashscore.fr/equipe/bordeaux/SKc9FeQ7/resultats/",
        "rugby": "https://www.flashscore.fr/equipe/bordeaux-begles/hzHredXK/resultats/",
        "rugby_f": "https://www.flashscore.fr/equipe/stade-bordelais/6kHcQsxn/resultats/",
        "hockey": "https://www.flashscore.fr/equipe/bordeaux/n9V6xiyI/resultats/",
        "volley": "https://www.flashscore.fr/equipe/bordeaux/pYHXelap/resultats/",
        "basket": "https://www.jsa-bmb.fr/"
    }

    # 1) FOOTBALL - via RSS (rapide en requests)
    football_data = scrape_rss_football()[:20]

    # 2) BASKET - via requests direct
    basket_data = scrape_jsa_bmb(urls["basket"])[:20]

    # 3) RUGBY, RUGBY_F, HOCKEY - via un seul driver Selenium
    flashscore_urls = [
        ("rugby",    urls["rugby"]),
        ("rugby_f",  urls["rugby_f"]),
        ("hockey",   urls["hockey"]),
        ("volley",   urls["volley"])
    ]
    flashscore_data = scrape_flashscore_for_sports(flashscore_urls)

    # Combine les résultats dans un dict global
    combined_results = {
        "football": football_data,
        "basket": basket_data,
        "rugby":   flashscore_data.get("rugby", []),
        "rugby_f": flashscore_data.get("rugby_f", []),
        "hockey":  flashscore_data.get("hockey", []),
        "volley":  flashscore_data.get("volley", [])
    }

    # Enregistrement en JSON
    project_dir = os.path.dirname(os.path.abspath(__file__))
    data_dir = os.path.join(project_dir, 'public', 'data')
    os.makedirs(data_dir, exist_ok=True)
    json_path = os.path.join(data_dir, 'resultats.json')

    with open(json_path, 'w', encoding='utf-8') as f:
        # Pour gagner en vitesse d'écriture, vous pouvez mettre indent=None ou supprimer "indent=4"
        json.dump(combined_results, f, ensure_ascii=False, indent=4)

    print(f"Résultats enregistrés dans {json_path}")


if __name__ == "__main__":
    main()
