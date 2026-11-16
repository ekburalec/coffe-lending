export function initImageFallbacks() {
  const images = document.querySelectorAll('[data-image-fallback]');
  if (!images.length) return;

  function showFallback(image) {
    const isReview = image.dataset.imageFallback === 'review';
    const source = isReview ? 'assets/images/review-placeholder.jpg' : 'assets/images/product-placeholder.jpg';
    if (image.getAttribute('src') === source) return;
    image.removeAttribute('srcset');
    image.removeAttribute('sizes');
    image.setAttribute('src', source);
    image.alt = isReview ? '' : 'Изображение товара отсутствует';
  }

  document.addEventListener('error', event => {
    const image = event.target;
    if (image instanceof HTMLImageElement && image.hasAttribute('data-image-fallback')) {
      showFallback(image);
    }
  }, true);

  images.forEach(image => {
    if (!image.getAttribute('src')?.trim() || (image.complete && image.naturalWidth === 0)) {
      showFallback(image);
    }
  });
}
