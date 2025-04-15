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
                this.currentIndex = (this.currentIndex - 1 + this.articles.length) % this.articles.length;
            }, 5000);
        },
        pause() {
            clearInterval(this.interval);
        }
    };
}

export function sliderCategorie() {
    return {
        items: [
            { title: 'Volley', image: '/medias/volley-merginac.webp', slug: 'volleyball' },
            { title: 'Football', image: '', slug: 'football' },
            { title: 'Rugby', image: '/medias/ubb-rugby-_Enj.E23.webp', slug: 'rugby' },
            { title: 'Rugby Amateur', image: '', slug: 'rugby-amateur' },
            { title: 'Hockey sur glace', image: '/medias/hockey-sur-glace.webp', slug: 'hockey-sur-glace' },
            { title: 'Basket', image: '', slug: 'basketball' }
        ],
        slider: null,
        init() {
            this.slider = this.$refs.slider;
            this.items = [...this.items, ...this.items];
        },
        scrollLeft() {
            this.slider.scrollBy({ left: -300, behavior: 'smooth' });
        },
        scrollRight() {
            this.slider.scrollBy({ left: 300, behavior: 'smooth' });
        }
    };
}
