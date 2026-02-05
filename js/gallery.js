// Gallery and Lightbox
document.addEventListener('DOMContentLoaded', function() {
    const galleryImages = document.querySelectorAll('.gallery-item img');
    
    if (galleryImages.length === 0) return;

    // Create lightbox
    const lightbox = document.createElement('div');
    lightbox.className = 'lightbox';
    lightbox.innerHTML = `
        <div class="lightbox-content">
            <span class="lightbox-close">&times;</span>
            <span class="lightbox-prev">&#10094;</span>
            <span class="lightbox-next">&#10095;</span>
            <img src="" alt="">
            <div class="lightbox-counter"></div>
        </div>
    `;
    document.body.appendChild(lightbox);

    const lightboxImg = lightbox.querySelector('img');
    const closeBtn = lightbox.querySelector('.lightbox-close');
    const prevBtn = lightbox.querySelector('.lightbox-prev');
    const nextBtn = lightbox.querySelector('.lightbox-next');
    const counter = lightbox.querySelector('.lightbox-counter');

    let currentIndex = 0;
    const images = Array.from(galleryImages);

    function showImage(index) {
        if (index < 0) index = images.length - 1;
        if (index >= images.length) index = 0;
        
        currentIndex = index;
        lightboxImg.src = images[index].src;
        counter.textContent = `${index + 1} / ${images.length}`;
    }

    // Add click event to gallery images
    galleryImages.forEach((img, index) => {
        img.style.cursor = 'pointer';
        img.addEventListener('click', function() {
            lightbox.classList.add('active');
            showImage(index);
        });
    });

    // Close lightbox
    closeBtn.addEventListener('click', function() {
        lightbox.classList.remove('active');
    });

    lightbox.addEventListener('click', function(e) {
        if (e.target === lightbox) {
            lightbox.classList.remove('active');
        }
    });

    // Navigation
    prevBtn.addEventListener('click', function() {
        showImage(currentIndex - 1);
    });

    nextBtn.addEventListener('click', function() {
        showImage(currentIndex + 1);
    });

    // Keyboard navigation
    document.addEventListener('keydown', function(e) {
        if (!lightbox.classList.contains('active')) return;
        
        if (e.key === 'Escape') {
            lightbox.classList.remove('active');
        } else if (e.key === 'ArrowLeft') {
            showImage(currentIndex - 1);
        } else if (e.key === 'ArrowRight') {
            showImage(currentIndex + 1);
        }
    });
});

// Add lightbox CSS
if (!document.querySelector('style[data-lightbox-styles]')) {
    const style = document.createElement('style');
    style.setAttribute('data-lightbox-styles', 'true');
    style.textContent = `
        .lightbox {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.95);
            z-index: 10000;
            align-items: center;
            justify-content: center;
        }
        .lightbox.active {
            display: flex;
        }
        .lightbox-content {
            position: relative;
            max-width: 90%;
            max-height: 90%;
        }
        .lightbox-content img {
            max-width: 100%;
            max-height: 90vh;
            display: block;
        }
        .lightbox-close {
            position: absolute;
            top: -40px;
            right: 0;
            font-size: 40px;
            color: white;
            cursor: pointer;
            font-weight: 300;
            transition: color 0.3s;
        }
        .lightbox-close:hover {
            color: #D4AF37;
        }
        .lightbox-prev,
        .lightbox-next {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            font-size: 40px;
            color: white;
            cursor: pointer;
            padding: 10px;
            user-select: none;
            transition: color 0.3s;
        }
        .lightbox-prev:hover,
        .lightbox-next:hover {
            color: #D4AF37;
        }
        .lightbox-prev {
            left: -60px;
        }
        .lightbox-next {
            right: -60px;
        }
        .lightbox-counter {
            position: absolute;
            bottom: -40px;
            left: 50%;
            transform: translateX(-50%);
            color: white;
            font-size: 16px;
        }
        @media (max-width: 768px) {
            .lightbox-prev {
                left: 10px;
            }
            .lightbox-next {
                right: 10px;
            }
            .lightbox-close {
                top: 10px;
                right: 10px;
            }
        }
    `;
    document.head.appendChild(style);
}
