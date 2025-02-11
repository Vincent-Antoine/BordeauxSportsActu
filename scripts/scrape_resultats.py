import json
import os
import requests
import re
import xml.etree.ElementTree as ET
from datetime import datetime
from email.utils import parsedate_to_datetime  # <-- pour parser la pubDate
from bs4 import BeautifulSoup
from concurrent.futures import ThreadPoolExecutor, as_completed
from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.chrome.service import Service
from webdriver_manager.chrome import ChromeDriverManager
from selenium.webdriver.chrome.options import Options
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
import time

################################################################################
#                          FONCTION RSS POUR LE FOOTBALL                       #
################################################################################

def scrape_rss_football():
    """
    Récupère et parse les résultats de football depuis le flux RSS de matchendirect.
    Retourne une liste de dictionnaires de la forme :
    [
        {
            "date_heure": str,
            "home_team": str,
            "away_team": str,
            "home_score": str,
            "away_score": str
        },
        ...
    ]
    """
    import requests
    import re
    import xml.etree.ElementTree as ET
    from datetime import datetime
    from email.utils import parsedate_to_datetime

    rss_url = "https://www.matchendirect.fr/rss/foot-bordeaux-e891.xml"
    results = []

    # On définit un en-tête pour être identifié comme un vrai navigateur :
    headers = {
        "User-Agent": (
            "Mozilla/5.0 (Windows NT 10.0; Win64; x64) "
            "AppleWebKit/537.36 (KHTML, like Gecko) "
            "Chrome/100.0.4896.127 Safari/537.36"
        )
    }

    try:
        # On passe l'en-tête au get :
        response = requests.get(rss_url, headers=headers, timeout=10)
        response.raise_for_status()

        root = ET.fromstring(response.content)

        for item in root.findall(".//item"):
            title_element = item.find("title")
            pub_date_element = item.find("pubDate")

            # Si l'un des deux est introuvable, on passe au suivant
            if title_element is None or pub_date_element is None:
                continue

            title = title_element.text.strip() if title_element.text else ""
            pub_date = pub_date_element.text.strip() if pub_date_element.text else ""

            # Ne traiter que les matchs avec "score final"
            if "score final" in title.lower():
                # Convertir la date RSS en format JJ.MM.AAAA
                try:
                    dt = parsedate_to_datetime(pub_date)
                    match_date = dt.strftime("%d.%m.%Y")
                except Exception as e:
                    print(f"Skipping item à cause d'une erreur de parsing de date : {e}")
                    continue

                # On capture la compétition (1er groupe), les 2 équipes (2e et 3e groupes),
                # puis les 2 scores (4e et 5e groupes).
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
        print(f"Erreur lors de la récupération du flux RSS football : {e}")

    return results

################################################################################
#             FONCTIONS EXISTANTES POUR LES AUTRES SPORTS (FLASHSCORE)         #
################################################################################

def fetch_flashscore(url):
    try:
        options = Options()
        options.add_argument("--headless")
        options.add_argument("--disable-gpu")
        options.add_argument("--no-sandbox")
        options.add_argument("--window-size=1920,1080")
        options.add_argument("--disable-dev-shm-usage")
        options.add_argument("--disable-blink-features=AutomationControlled")

        driver = webdriver.Chrome(service=Service(ChromeDriverManager().install()), options=options)
        driver.get(url)

        # Attendre que les matchs soient visibles
        WebDriverWait(driver, 15).until(
            EC.presence_of_all_elements_located((By.CLASS_NAME, "event__match"))
        )

        # Petit délai pour s'assurer du chargement complet
        time.sleep(5)

        html_content = driver.page_source
        driver.quit()

        soup = BeautifulSoup(html_content, 'lxml')
        return soup

    except Exception as e:
        print(f"Erreur lors de la récupération de {url} avec Selenium : {e}")
        return None


def parse_flashscore(soup, sport_type):
    results = []
    try:
        match_divs = soup.find_all(class_="event__match")
        for match in match_divs:
            try:
                match_time_el = match.find(class_="event__time")
                match_time = match_time_el.get_text(strip=True) if match_time_el else None

                home_participant_el = match.find(class_="event__participant--home")
                home_team = home_participant_el.get_text(strip=True) if home_participant_el else None

                away_participant_el = match.find(class_="event__participant--away")
                away_team = away_participant_el.get_text(strip=True) if away_participant_el else None

                home_score_el = match.find(class_="event__score--home")
                home_score = home_score_el.get_text(strip=True) if home_score_el else None

                away_score_el = match.find(class_="event__score--away")
                away_score = away_score_el.get_text(strip=True) if away_score_el else None

                # On s'assure que toutes les informations sont présentes
                if all([match_time, home_team, away_team, home_score, away_score]):
                    results.append({
                        "date_heure": match_time,
                        "home_team": home_team,
                        "away_team": away_team,
                        "home_score": home_score,
                        "away_score": away_score
                    })
            except Exception as e:
                print(f"Erreur lors de l'extraction d'un match de {sport_type} : {e}")
                continue
    except Exception as e:
        print(f"Erreur lors du parsing de flashscore.fr pour {sport_type} : {e}")
    return results

def scrape_flashscore(url, sport_type):
    soup = fetch_flashscore(url)
    if not soup:
        return []
    return parse_flashscore(soup, sport_type)

################################################################################
#              FONCTIONS EXISTANTES POUR LE BASKET (JSA-BMB.FR)                #
################################################################################

def fetch_jsa_bmb(url):
    headers = {
        "User-Agent": "Mozilla/5.0 (compatible; ScraperBot/1.0; +http://yourdomain.com/bot)"
    }
    try:
        response = requests.get(url, headers=headers, timeout=10)
        response.raise_for_status()
        return BeautifulSoup(response.text, 'lxml')
    except requests.RequestException as e:
        print(f"Erreur lors de la récupération de {url} : {e}")
        return None

def parse_jsa_bmb(soup):
    results = []
    try:
        match_divs = soup.find_all(class_="widget-results__item")
        for match in match_divs:
            try:
                date_time_el = match.find("time")
                date_time = date_time_el.get_text(strip=True) if date_time_el else None

                home_team_el = match.find(class_="widget-results__team--odd")
                home_team = home_team_el.get_text(strip=True) if home_team_el else None

                away_team_el = match.find(class_="widget-results__team--even")
                away_team = away_team_el.get_text(strip=True) if away_team_el else None

                home_score_el = match.find(class_="widget-results__score-winner")
                home_score = home_score_el.get_text(strip=True) if home_score_el else None

                away_score_el = match.find(class_="widget-results__score-loser")
                away_score = away_score_el.get_text(strip=True) if away_score_el else None

                if all([date_time, home_team, away_team, home_score, away_score]):
                    results.append({
                        "date_heure": date_time,
                        "home_team": home_team,
                        "away_team": away_team,
                        "home_score": home_score,
                        "away_score": away_score
                    })
            except Exception as e:
                print(f"Erreur lors de l'extraction d'un match de basketball : {e}")
                continue
    except Exception as e:
        print(f"Erreur lors du parsing de jsa-bmb.fr : {e}")
    return results

def scrape_jsa_bmb(url, sport_type):
    soup = fetch_jsa_bmb(url)
    if not soup:
        return []
    return parse_jsa_bmb(soup)

################################################################################
#              ROUTAGE DU SCRAPING EN FONCTION DU SPORT                        #
################################################################################

def scrape_results(url, sport_type):
    """
    Redirige vers la fonction de scraping adaptée en fonction du sport.
    - Football : Récupération via flux RSS (scrape_rss_football)
    - Rugby, Rugby_F, Hockey : Récupération via FlashScore
    - Basket : Récupération via JSA BMB
    """
    if sport_type == "football":
        return scrape_rss_football()

    elif sport_type in ["rugby", "rugby_f", "hockey"]:
        return scrape_flashscore(url, sport_type)

    elif sport_type == "basket":
        return scrape_jsa_bmb(url, sport_type)

    else:
        print(f"Sport non supporté : {sport_type}")
        return []

################################################################################
#                   FONCTION PRINCIPALE (MAIN)                                 #
################################################################################

def main():
    """
    Lance le scraping pour différents sports,
    stocke les résultats dans un dictionnaire unique,
    puis enregistre ce dictionnaire en JSON dans 'public/data/resultats.json'.
    """

    urls = {
        "football": "https://www.flashscore.fr/equipe/bordeaux/SKc9FeQ7/resultats/",
        "rugby": "https://www.flashscore.fr/equipe/bordeaux-begles/hzHredXK/resultats/",
        "rugby_f": "https://www.flashscore.fr/equipe/stade-bordelais/6kHcQsxn/resultats/",
        "hockey": "https://www.flashscore.fr/equipe/bordeaux/n9V6xiyI/resultats/",
        "basket": "https://www.jsa-bmb.fr/"
    }

    combined_results = {}

    # Exécuter le scraping pour chaque sport en parallèle (ThreadPoolExecutor)
    with ThreadPoolExecutor(max_workers=5) as executor:
        future_to_sport = {
            executor.submit(scrape_results, url, sport): sport
            for sport, url in urls.items()
        }

        for future in as_completed(future_to_sport):
            sport = future_to_sport[future]
            try:
                data = future.result()
                combined_results[sport] = data
                print(f"Scraping terminé pour {sport} ({len(data)} résultats)")
            except Exception as e:
                print(f"Erreur lors du scraping pour {sport} : {e}")
                combined_results[sport] = []

    # Création du dossier public/data/ s'il n'existe pas
    project_dir = os.path.dirname(os.path.abspath(__file__))
    data_dir = os.path.join(project_dir, 'public', 'data')
    os.makedirs(data_dir, exist_ok=True)

    # Chemin du fichier JSON
    json_path = os.path.join(data_dir, 'resultats.json')

    # Écriture des résultats en JSON
    with open(json_path, 'w', encoding='utf-8') as f:
        json.dump(combined_results, f, ensure_ascii=False, indent=4)

    print(f"Les résultats ont été enregistrés dans {json_path}")


if __name__ == "__main__":
    main()
