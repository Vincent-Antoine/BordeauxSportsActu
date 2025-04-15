import './bootstrap.js'
import './styles/app.css'

import Alpine from 'alpinejs'
import { carouselComponent, sliderCategorie } from './js/home' // le chemin est relatif à assets/

window.Alpine = Alpine

// 🔐 Enregistrement des composants
Alpine.data('carouselComponent', carouselComponent)
Alpine.data('sliderCategorie', sliderCategorie)

Alpine.start()
