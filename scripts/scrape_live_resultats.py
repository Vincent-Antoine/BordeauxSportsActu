import json
import os
from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.chrome.service import Service
from selenium.webdriver.chrome.options import Options
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
from webdriver_manager.chrome import ChromeDriverManager

URLS = {
    "football": "https://www.flashscore.fr/equipe/cavalier/UuOW3jq1/",
    "rugby": "https://www.flashscore.fr/equipe/bordeaux-begles/hzHredXK/",
    "rugby_f": "https://www.flashscore.fr/equipe/stade-bordelais/6kHcQsxn/",
    "hockey": "https://www.flashscore.fr/equipe/bordeaux/n9V6xiyI/"
}

def scrape_live_match(sport, url):
    options = Options()
    options.add_argument("--headless")
    options.add_argument("--disable-gpu")
    options.add_argument("--no-sandbox")
    options.add_argument("--disable-dev-shm-usage")

    driver = webdriver.Chrome(service=Service(ChromeDriverManager().install()), options=options)

    try:
        driver.get(url)

        WebDriverWait(driver, 10).until(
            EC.presence_of_all_elements_located((By.CLASS_NAME, "event__match"))
        )

        live_match = driver.find_elements(By.CLASS_NAME, "event__match--live")
        if not live_match:
            return {sport: {"message": "Aucun match en live actuellement"}}

        match_time = driver.find_element(By.CLASS_NAME, "event__stage--block").text
        home_team = driver.find_element(By.CLASS_NAME, "event__homeParticipant").find_element(By.TAG_NAME, "span").text
        away_team = driver.find_element(By.CLASS_NAME, "event__awayParticipant").find_element(By.TAG_NAME, "span").text
        home_score = driver.find_element(By.CLASS_NAME, "event__score--home").text
        away_score = driver.find_element(By.CLASS_NAME, "event__score--away").text

        return {
            sport: {
                "match_time": match_time,
                "home_team": home_team,
                "away_team": away_team,
                "home_score": home_score,
                "away_score": away_score
            }
        }
    except Exception as e:
        print(f"Erreur pour {sport} : {e}")
        return {sport: {"message": "Erreur lors du scraping"}}
    finally:
        driver.quit()

def save_to_json(data):
    project_dir = os.path.dirname(os.path.abspath(__file__))
    json_path = os.path.join(project_dir, "resultats_live.json")
    with open(json_path, "w", encoding="utf-8") as json_file:
        json.dump(data, json_file, ensure_ascii=False, indent=4)
    print(f"Données enregistrées dans {json_path}")

if __name__ == "__main__":
    all_results = {}
    for sport, url in URLS.items():
        result = scrape_live_match(sport, url)
        all_results.update(result)
    save_to_json(all_results)
