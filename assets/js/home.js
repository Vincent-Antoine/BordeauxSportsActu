export function carouselComponent() {
  return {
    articles: [],
    currentIndex: 0,
    interval: null,
    init() {
      this.articles = window.sliderArticles ?? [];
      if (this.articles.length > 1) this.start();
    },
    start() {
      this.interval = setInterval(() => {
        this.currentIndex =
          (this.currentIndex - 1 + this.articles.length) % this.articles.length;
      }, 5000);
    },
    pause() {
      clearInterval(this.interval);
    },
  };
}

export function sliderCategorie() {
  return {
    items: [
      {
        title: "Volley",
        image: "/medias/volley-categorie.webp",
        alt: "Match de volley à Mérignac",
        slug: "volleyball",
      },
      {
        title: "Football",
        image: "/medias/categorie-football.webp",
        alt: "Match de football avec plusieurs joueurs sur le terrain",
        slug: "football",
      },
      {
        title: "Rugby",
        image: "/medias/ubb-rugby-_Enj.E23.webp",
        alt: "Joueurs de l’Union Bordeaux Bègles en match",
        slug: "rugby",
      },
      // {
      //   title: "Rugby Amateur",
      //   image: "",
      //   alt: "Match de rugby amateur avec plusieurs joueurs",
      //   slug: "rugby-amateur",
      // },
      {
        title: "Hockey sur glace",
        image: "/medias/hockey-sur-glace.webp",
        alt: "Action de jeu lors d’un match de hockey sur glace",
        slug: "hockey-sur-glace",
      },
      {
        title: "Basket",
        image: "/medias/JSA-BMB.webp",
        alt: "Matche de basketball avec plusieurs joueurs sur le terrain",
        slug: "basketball",
      },
    ],
    slider: null,
    init() {
      this.slider = this.$refs.slider;
      this.items = [...this.items, ...this.items];
    },
    scrollLeft() {
      this.slider.scrollBy({ left: -300, behavior: "smooth" });
    },
    scrollRight() {
      this.slider.scrollBy({ left: 300, behavior: "smooth" });
    },
  };
}
