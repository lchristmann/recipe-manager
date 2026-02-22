import PhotoSwipeLightbox from 'photoswipe/lightbox';
import 'photoswipe/style.css';

// PhotoSwipe documentation: https://photoswipe.com/getting-started/
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.pswp-gallery').forEach((gallery) => {
        const lightbox = new PhotoSwipeLightbox({
            gallery: gallery,
            children: 'a',
            pswpModule: () => import('photoswipe')
        });
        lightbox.init();
    });
});
