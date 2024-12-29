import json
import os
import requests
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

        # Ajouter un délai explicite pour garantir le chargement complet de la page
        time.sleep(5)  # Délai de 5 secondes

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

                if sport_type == "football":
                    home_participant_el = match.find(class_="event__homeParticipant")
                    home_team_span = home_participant_el.find("span") if home_participant_el else None
                    home_team = home_team_span.get_text(strip=True) if home_team_span else None

                    away_participant_el = match.find(class_="event__awayParticipant")
                    away_team_span = away_participant_el.find("span") if away_participant_el else None
                    away_team = away_team_span.get_text(strip=True) if away_team_span else None
                else:
                    home_participant_el = match.find(class_="event__participant--home")
                    home_team = home_participant_el.get_text(strip=True) if home_participant_el else None

                    away_participant_el = match.find(class_="event__participant--away")
                    away_team = away_participant_el.get_text(strip=True) if away_participant_el else None

                home_score_el = match.find(class_="event__score--home")
                home_score = home_score_el.get_text(strip=True) if home_score_el else None

                away_score_el = match.find(class_="event__score--away")
                away_score = away_score_el.get_text(strip=True) if away_score_el else None

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

def scrape_flashscore(url, sport_type):
    soup = fetch_flashscore(url)
    if not soup:
        return []
    return parse_flashscore(soup, sport_type)

def scrape_jsa_bmb(url, sport_type):
    soup = fetch_jsa_bmb(url)
    if not soup:
        return []
    return parse_jsa_bmb(soup)

def scrape_results(url, sport_type):
    if sport_type in ["football", "rugby", "rugby_f", "hockey"]:
        return scrape_flashscore(url, sport_type)
    elif sport_type == "basket":
        return scrape_jsa_bmb(url, sport_type)
    else:
        print(f"Sport non supporté : {sport_type}")
        return []

def main():
    urls = {
        "football": "https://www.flashscore.fr/equipe/bordeaux/SKc9FeQ7/resultats/",
        "rugby": "https://www.flashscore.fr/equipe/bordeaux-begles/hzHredXK/resultats/",
        "rugby_f": "https://www.flashscore.fr/equipe/stade-bordelais/6kHcQsxn/resultats/",
        "hockey": "https://www.flashscore.fr/equipe/bordeaux/n9V6xiyI/resultats/",
        "basket": "https://www.jsa-bmb.fr/"
    }

    combined_results = {}

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

    project_dir = os.path.dirname(os.path.abspath(__file__))
    data_dir = os.path.join(project_dir, 'public', 'data')
    os.makedirs(data_dir, exist_ok=True)

    json_path = os.path.join(data_dir, 'resultats.json')

    with open(json_path, 'w', encoding='utf-8') as f:
        json.dump(combined_results, f, ensure_ascii=False, indent=4)

    print(f"Les résultats ont été enregistrés dans {json_path}")

if __name__ == "__main__":
    main()
