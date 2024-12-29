import json
import os
from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.chrome.service import Service
from selenium.webdriver.chrome.options import Options
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
from webdriver_manager.chrome import ChromeDriverManager

# URLs à scraper
URLS = {
    "Football - Girondins de Bordeaux": "https://www.flashscore.fr/equipe/bordeaux/SKc9FeQ7/",
    "Rugby - UBB": "https://www.flashscore.fr/equipe/bordeaux-begles/hzHredXK/",
    "Rugby Féminin - Les Lionnes": "https://www.flashscore.fr/equipe/stade-bordelais/6kHcQsxn/",
    "Hockey - Boxers de Bordeaux": "https://www.flashscore.fr/equipe/bordeaux/n9V6xiyI/"
}

# Durée maximale d'attente pour que les éléments soient présents (en secondes)
TIMEOUT = 1

def scrape_all_matches(url_dict):
    """
    Scrape toutes les URLs définies dans url_dict en utilisant une seule instance de Chrome.
    Retourne un dictionnaire avec les résultats ou les messages d'erreur.
    """

    # Configuration de Chrome (mode headless)
    options = Options()
    options.add_argument("--headless")
    options.add_argument("--disable-gpu")
    options.add_argument("--no-sandbox")
    options.add_argument("--disable-dev-shm-usage")
    options.add_argument("--blink-settings=imagesEnabled=false")


    driver = webdriver.Chrome(
        service=Service(ChromeDriverManager().install()),
        options=options
    )

    all_results = {}

    try:
        for sport, url in url_dict.items():
            print(f"--- Scraping {sport} ---")
            try:
                # Ouvre l'URL
                driver.get(url)
                
                # Attend la présence d'au moins un élément de classe "event__match"
                WebDriverWait(driver, TIMEOUT).until(
                    EC.presence_of_all_elements_located((By.CLASS_NAME, "event__match"))
                )

                # Vérifie s'il y a un match en direct ("event__match--live")
                live_match = driver.find_elements(By.CLASS_NAME, "event__match--live")
                if not live_match:
                    all_results[sport] = {"message": "Aucun match en live actuellement"}
                    continue

                # Si on a trouvé un match live, on récupère les informations
                match_time = driver.find_element(By.CLASS_NAME, "event__stage--block").text
                home_team = driver.find_element(By.CLASS_NAME, "event__homeParticipant")\
                                  .find_element(By.TAG_NAME, "span").text
                away_team = driver.find_element(By.CLASS_NAME, "event__awayParticipant")\
                                  .find_element(By.TAG_NAME, "span").text
                home_score = driver.find_element(By.CLASS_NAME, "event__score--home").text
                away_score = driver.find_element(By.CLASS_NAME, "event__score--away").text

                all_results[sport] = {
                    "match_time": match_time,
                    "home_team": home_team,
                    "away_team": away_team,
                    "home_score": home_score,
                    "away_score": away_score
                }

            except Exception as e:
                print(f"Erreur lors du scraping pour {sport} : {e}")
                all_results[sport] = {"message": "Erreur lors du scraping"}

    finally:
        # Quoi qu'il arrive (même en cas d'erreur), on ferme le driver
        driver.quit()

    return all_results

def save_to_json(data):
    """
    Sauvegarde les données 'data' au format JSON dans le fichier 'resultats_live.json'
    situé dans le même répertoire que ce script.
    """
    # Chemin absolu du répertoire courant (emplacement du script)
    project_dir = os.path.dirname(os.path.abspath(__file__))
    json_path = os.path.join(project_dir, "resultats_live.json")

    with open(json_path, "w", encoding="utf-8") as json_file:
        json.dump(data, json_file, ensure_ascii=False, indent=4)

    print(f"Données enregistrées dans {json_path}")

if __name__ == "__main__":
    all_results = scrape_all_matches(URLS)
    save_to_json(all_results)
