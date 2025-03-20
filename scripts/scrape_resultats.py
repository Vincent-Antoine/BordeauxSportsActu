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
# from webdriver_manager.chrome import ChromeDriverManager
from selenium.webdriver.chrome.options import Options
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
import logging

# Configuration du logging vers un fichier
logging.basicConfig(
    filename='scraping.log',
    filemode='w',
    level=logging.DEBUG,
    format='%(asctime)s [%(levelname)s] %(message)s'
)

###############################################################################
#               SCRAPER FLASHSCORE (DIFFÉRENCIÉ POUR FOOTBALL ET AUTRES)      #
###############################################################################

def parse_flashscore_page(driver, url, sport=None):
    """
    Ouvre une URL FlashScore, attend que les matchs soient présents,
    et renvoie la liste des résultats.
    Pour le football, le parsing est adapté à la nouvelle structure HTML en
    utilisant des sélecteurs génériques.
    Pour les autres sports, la méthode initiale est utilisée.
    """
    if sport == "football":
        logging.info(f"Scraping de l'URL (football) : {url}")
    results = []
    try:
        driver.get(url)
        if sport == "football":
            logging.debug("URL chargée, attente des éléments 'event__match' (football)...")
        WebDriverWait(driver, 15).until(
            EC.presence_of_all_elements_located((By.CLASS_NAME, "event__match"))
        )
        time.sleep(1)
        soup = BeautifulSoup(driver.page_source, 'lxml')
        # Récupère tous les conteneurs de match
        match_divs = soup.find_all("div", class_="event__match")
        if sport == "football":
            logging.info(f"{len(match_divs)} éléments 'event__match' trouvés (football).")
        for idx, match in enumerate(match_divs):
            try:
                if sport == "football":
                    # Utilisation de sélecteurs génériques :
                    # - L'heure est dans <div class="event__time">
                    # - L'équipe à domicile est dans <div class="event__homeParticipant">
                    # - L'équipe à l'extérieur est dans <div class="event__awayParticipant">
                    # - Les scores (si présents) dans des <span> avec classes "event__score--home" et "event__score--away"
                    time_el = match.find("div", class_="event__time")
                    home_div = match.find("div", class_="event__homeParticipant")
                    away_div = match.find("div", class_="event__awayParticipant")
                    home_score_el = match.select_one("span.event__score--home")
                    away_score_el = match.select_one("span.event__score--away")
                    
                    if time_el and home_div and away_div:
                        date_heure = time_el.get_text(strip=True)
                        # Extraction du texte contenu dans le conteneur générique
                        home_team = home_div.get_text(strip=True)
                        away_team = away_div.get_text(strip=True)
                        home_score = home_score_el.get_text(strip=True) if home_score_el else ""
                        away_score = away_score_el.get_text(strip=True) if away_score_el else ""
                        
                        result = {
                            "date_heure": date_heure,
                            "home_team": home_team,
                            "away_team": away_team,
                            "home_score": home_score,
                            "away_score": away_score
                        }
                        results.append(result)
                        logging.debug(f"Match {idx+1} parsé (football) : {result}")
                    else:
                        logging.warning(f"Match {idx+1} (football) ignoré pour éléments manquants.")
                else:
                    # Pour les autres sports, on utilise la méthode initiale
                    match_time_el = match.find(class_="event__time")
                    home_participant_el = match.find(class_="event__participant--home")
                    away_participant_el = match.find(class_="event__participant--away")
                    home_score_el = match.find(class_="event__score--home")
                    away_score_el = match.find(class_="event__score--away")
                    
                    if match_time_el and home_participant_el and away_participant_el:
                        result = {
                            "date_heure": match_time_el.get_text(strip=True),
                            "home_team": home_participant_el.get_text(strip=True),
                            "away_team": away_participant_el.get_text(strip=True),
                            "home_score": home_score_el.get_text(strip=True) if home_score_el else "",
                            "away_score": away_score_el.get_text(strip=True) if away_score_el else ""
                        }
                        results.append(result)
            except Exception as e:
                if sport == "football":
                    logging.error(f"Erreur lors du parsing d'un match (football) sur {url} : {e}")
                continue
    except Exception as e:
        if sport == "football":
            logging.error(f"Erreur lors du parsing de {url} (football) : {e}")
    return results


def scrape_flashscore_for_sports(flashscore_urls):
    """
    Lance un seul navigateur Selenium pour tous les sports passés dans flashscore_urls.
    flashscore_urls est une liste de tuples (sport_key, url).
    Retourne un dictionnaire { "sport": [ ...résultats... ], ... }.
    Les logs détaillés ne sont enregistrés que pour le football.
    """
    options = Options()
    options.add_argument("--headless")
    options.add_argument("--disable-gpu")
    options.add_argument("--no-sandbox")
    options.add_argument("--disable-dev-shm-usage")
    options.add_argument("--disable-blink-features=AutomationControlled")
    options.add_argument("--window-size=1920,1080")

    driver = webdriver.Chrome(options=options)
    # Pour webdriver_manager, décommentez la ligne ci-dessous :
    # driver = webdriver.Chrome(service=Service(ChromeDriverManager().install()), options=options)

    results_dict = {}
    try:
        for sport_key, url in flashscore_urls:
            if sport_key == "football":
                logging.info(f"Scraping du sport (football) : {sport_key} - URL : {url}")
            parsed_data = parse_flashscore_page(driver, url, sport=sport_key)
            results_dict[sport_key] = parsed_data[:20]
            if sport_key == "football":
                logging.info(f"{len(parsed_data)} résultats récupérés pour {sport_key}.")
    finally:
        driver.quit()
        if any(sport_key == "football" for sport_key, _ in flashscore_urls):
            logging.info("Fermeture du driver Selenium.")
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
        for idx, match in enumerate(match_divs):
            try:
                date_time_el = match.find("time")
                home_team_el = match.find(class_="widget-results__team--odd")
                away_team_el = match.find(class_="widget-results__team--even")
                home_score_el = match.find(class_="widget-results__score-winner")
                away_score_el = match.find(class_="widget-results__score-loser")
                
                if date_time_el and home_team_el and away_team_el:
                    result = {
                        "date_heure": date_time_el.get_text(strip=True),
                        "home_team": home_team_el.get_text(strip=True),
                        "away_team": away_team_el.get_text(strip=True),
                        "home_score": home_score_el.get_text(strip=True) if home_score_el else "",
                        "away_score": away_score_el.get_text(strip=True) if away_score_el else ""
                    }
                    results.append(result)
            except Exception as e:
                continue
    except requests.RequestException as e:
        pass
    return results


###############################################################################
#                               MAIN                                           #
###############################################################################

def main():
    urls = {
        "football": "https://www.flashscore.fr/equipe/bordeaux/SKc9FeQ7/resultats/",
        "rugby": "https://www.flashscore.fr/equipe/bordeaux-begles/hzHredXK/resultats/",
        "rugby_f": "https://www.flashscore.fr/equipe/stade-bordelais/6kHcQsxn/resultats/",
        "hockey": "https://www.flashscore.fr/equipe/bordeaux/n9V6xiyI/resultats/",
        "volley": "https://www.flashscore.fr/equipe/bordeaux/pYHXelap/resultats/",
        "basket": "https://www.jsa-bmb.fr/"
    }

    # Scraping du basket via requête directe
    basket_data = scrape_jsa_bmb(urls["basket"])[:20]

    # Scraping des autres sports via Selenium
    flashscore_urls = [
        ("football", urls["football"]),
        ("rugby", urls["rugby"]),
        ("rugby_f", urls["rugby_f"]),
        ("hockey", urls["hockey"]),
        ("volley", urls["volley"])
    ]
    flashscore_data = scrape_flashscore_for_sports(flashscore_urls)

    combined_results = {
        "football": flashscore_data.get("football", []),
        "basket": basket_data,
        "rugby": flashscore_data.get("rugby", []),
        "rugby_f": flashscore_data.get("rugby_f", []),
        "hockey": flashscore_data.get("hockey", []),
        "volley": flashscore_data.get("volley", [])
    }

    project_dir = os.path.dirname(os.path.abspath(__file__))
    data_dir = os.path.join(project_dir, 'public', 'data')
    os.makedirs(data_dir, exist_ok=True)
    json_path = os.path.join(data_dir, 'resultats.json')

    with open(json_path, 'w', encoding='utf-8') as f:
        json.dump(combined_results, f, ensure_ascii=False, indent=None)
    
    print(f"Résultats enregistrés dans {json_path}")
    # Le fichier de logs 'scraping.log' contiendra les logs détaillés pour le football.

if __name__ == "__main__":
    main()
