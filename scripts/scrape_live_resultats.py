import json
import os
from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.chrome.service import Service
from selenium.webdriver.chrome.options import Options
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
from webdriver_manager.chrome import ChromeDriverManager

def scrape_psg_live_match(url):
    # Configurer le navigateur Selenium avec des options en mode headless
    options = Options()
    options.add_argument("--headless")
    options.add_argument("--disable-gpu")
    options.add_argument("--no-sandbox")
    options.add_argument("--disable-dev-shm-usage")

    # Initialiser le driver
    driver = webdriver.Chrome(service=Service(ChromeDriverManager().install()), options=options)

    try:
        # Charger la page
        driver.get(url)

        # Attendre que les éléments de la page soient chargés
        WebDriverWait(driver, 10).until(
            EC.presence_of_all_elements_located((By.CLASS_NAME, "event__match"))
        )

        # Vérifier si un match en live est présent
        live_match = driver.find_elements(By.CLASS_NAME, "event__match--live")
        if not live_match:
            save_to_json({"message": "Aucun match en live actuellement"})
            return

        # Récupérer les données du match
        match_time = driver.find_element(By.CLASS_NAME, "event__stage--block").text
        home_team = driver.find_element(By.CLASS_NAME, "event__homeParticipant").find_element(By.TAG_NAME, "span").text
        away_team = driver.find_element(By.CLASS_NAME, "event__awayParticipant").find_element(By.TAG_NAME, "span").text
        home_score = driver.find_element(By.CLASS_NAME, "event__score--home").text
        away_score = driver.find_element(By.CLASS_NAME, "event__score--away").text

        # Organiser les données dans un dictionnaire
        match_data = {
            "match_time": match_time,
            "home_team": home_team,
            "away_team": away_team,
            "home_score": home_score,
            "away_score": away_score
        }

        # Sauvegarder les données dans un fichier JSON
        save_to_json(match_data)

    except Exception as e:
        print(f"Erreur lors du scraping : {e}")
    finally:
        driver.quit()

def save_to_json(data):
    # Définir le chemin du fichier JSON
    project_dir = os.path.dirname(os.path.abspath(__file__))
    json_path = os.path.join(project_dir, "resultats_live.json")

    # Sauvegarder les données dans le fichier JSON
    with open(json_path, "w", encoding="utf-8") as json_file:
        json.dump(data, json_file, ensure_ascii=False, indent=4)

    print(f"Données enregistrées dans {json_path}")

if __name__ == "__main__":
    scrape_psg_live_match("https://www.flashscore.fr/equipe/bordeaux/SKc9FeQ7/")