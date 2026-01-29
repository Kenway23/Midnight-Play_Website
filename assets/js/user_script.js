// User-side JavaScript for Midnight Play

// Game Store Functionality
class GameStore {
    constructor() {
        this.games = [];
        this.filters = {
            search: '',
            sort: 'popular',
            price: 'all',
            category: 'all'
        };
        this.init();
    }

    init() {
        this.bindEvents();
        this.loadGames();
    }

    bindEvents() {
        // Search
        const searchInput = document.querySelector('.search-input');
        if (searchInput) {
            searchInput.addEventListener('input', (e) => {
                this.filters.search = e.target.value.toLowerCase();
                this.filterGames();
            });
        }

        // Sort
        const sortFilter = document.getElementById('sortFilter');
        if (sortFilter) {
            sortFilter.addEventListener('change', (e) => {
                this.filters.sort = e.target.value;
                this.sortGames();
            });
        }

        // Price filter
        const priceFilter = document.getElementById('priceFilter');
        if (priceFilter) {
            priceFilter.addEventListener('change', (e) => {
                this.filters.price = e.target.value;
                this.filterGames();
            });
        }

        // Category filter
        const categoryFilter = document.getElementById('categoryFilter');
        if (categoryFilter) {
            categoryFilter.addEventListener('change', (e) => {
                this.filters.category = e.target.value;
                this.filterGames();
            });
        }
    }

    async loadGames() {
        try {
            const response = await fetch('api/get_games.php');
            this.games = await response.json();
            this.renderGames();
        } catch (error) {
            console.error('Failed to load games:', error);
        }
    }

    filterGames() {
        let filtered = [...this.games];

        // Search filter
        if (this.filters.search) {
            filtered = filtered.filter(game =>
                game.title.toLowerCase().includes(this.filters.search) ||
                game.description.toLowerCase().includes(this.filters.search)
            );
        }

        // Price filter
        if (this.filters.price !== 'all') {
            filtered = filtered.filter(game => {
                switch (this.filters.price) {
                    case 'free':
                        return game.price === 0;
                    case 'under-50':
                        return game.price < 50000;
                    case '50-200':
                        return game.price >= 50000 && game.price <= 200000;
                    case 'over-200':
                        return game.price > 200000;
                    default:
                        return true;
                }
            });
        }

        // Category filter
        if (this.filters.category !== 'all') {
            filtered = filtered.filter(game =>
                game.category === this.filters.category
            );
        }

        this.renderGames(filtered);
    }

    sortGames() {
        let sorted = [...this.games];

        switch (this.filters.sort) {
            case 'newest':
                sorted.sort((a, b) => new Date(b.created_at) - new Date(a.created_at));
                break;
            case 'price-low':
                sorted.sort((a, b) => a.price - b.price);
                break;
            case 'price-high':
                sorted.sort((a, b) => b.price - a.price);
                break;
            default: // popular
                sorted.sort((a, b) => b.purchase_count - a.purchase_count);
        }

        this.renderGames(sorted);
    }

    renderGames(games = this.games) {
        const gameGrid = document.querySelector('.game-grid');
        if (!gameGrid) return;

        // In real implementation, this would update the DOM
        console.log('Rendering games:', games.length);
    }
}

// Shopping Cart
class ShoppingCart {
    constructor() {
        this.items = JSON.parse(localStorage.getItem('midnightplay_cart')) || [];
        this.init();
    }

    init() {
        this.updateCartCount();
    }

    addItem(gameId, gameTitle, price, quantity = 1) {
        const existingItem = this.items.find(item => item.id === gameId);

        if (existingItem) {
            existingItem.quantity += quantity;
        } else {
            this.items.push({
                id: gameId,
                title: gameTitle,
                price: price,
                quantity: quantity,
                addedAt: new Date().toISOString()
            });
        }

        this.saveCart();
        this.updateCartCount();
        this.showNotification(`${gameTitle} added to cart!`);
    }

    removeItem(gameId) {
        this.items = this.items.filter(item => item.id !== gameId);
        this.saveCart();
        this.updateCartCount();
    }

    updateQuantity(gameId, quantity) {
        const item = this.items.find(item => item.id === gameId);
        if (item) {
            item.quantity = quantity;
            if (quantity <= 0) {
                this.removeItem(gameId);
            } else {
                this.saveCart();
            }
        }
    }

    clearCart() {
        this.items = [];
        this.saveCart();
        this.updateCartCount();
    }

    getTotal() {
        return this.items.reduce((total, item) => total + (item.price * item.quantity), 0);
    }

    getItemCount() {
        return this.items.reduce((count, item) => count + item.quantity, 0);
    }

    saveCart() {
        localStorage.setItem('midnightplay_cart', JSON.stringify(this.items));
    }

    updateCartCount() {
        const cartCount = document.getElementById('cartCount');
        if (cartCount) {
            const count = this.getItemCount();
            cartCount.textContent = count;
            cartCount.style.display = count > 0 ? 'inline' : 'none';
        }
    }

    showNotification(message, type = 'success') {
        // Create notification element
        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;
        notification.innerHTML = `
            <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i>
            <span>${message}</span>
            <button class="notification-close"><i class="fas fa-times"></i></button>
        `;

        // Add to DOM
        document.body.appendChild(notification);

        // Show with animation
        setTimeout(() => notification.classList.add('show'), 10);

        // Auto remove
        setTimeout(() => {
            notification.classList.remove('show');
            setTimeout(() => notification.remove(), 300);
        }, 3000);

        // Close button
        notification.querySelector('.notification-close').addEventListener('click', () => {
            notification.classList.remove('show');
            setTimeout(() => notification.remove(), 300);
        });
    }
}

// User Authentication Helper
class AuthHelper {
    static checkSession() {
        const lastActivity = localStorage.getItem('lastActivity');
        if (lastActivity) {
            const inactiveTime = Date.now() - parseInt(lastActivity);
            const sessionTimeout = 30 * 60 * 1000; // 30 minutes

            if (inactiveTime > sessionTimeout) {
                this.logout();
                return false;
            }
        }

        localStorage.setItem('lastActivity', Date.now());
        return true;
    }

    static logout() {
        localStorage.removeItem('lastActivity');
        window.location.href = 'auth/auth_logout.php';
    }

    static updateActivity() {
        localStorage.setItem('lastActivity', Date.now());
    }
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', function () {
    // Initialize game store
    if (document.querySelector('.game-grid')) {
        window.gameStore = new GameStore();
    }

    // Initialize shopping cart
    window.shoppingCart = new ShoppingCart();

    // Session management
    AuthHelper.checkSession();

    // Update activity on user interaction
    document.addEventListener('click', AuthHelper.updateActivity);
    document.addEventListener('keypress', AuthHelper.updateActivity);

    // Add to cart buttons
    document.querySelectorAll('.add-to-cart').forEach(button => {
        button.addEventListener('click', function (e) {
            e.preventDefault();
            const gameId = this.dataset.gameId;
            const gameTitle = this.dataset.gameTitle;
            const price = parseFloat(this.dataset.price);

            window.shoppingCart.addItem(gameId, gameTitle, price);
        });
    });

    // Smooth scrolling for anchor links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            const href = this.getAttribute('href');
            if (href !== '#') {
                e.preventDefault();
                const target = document.querySelector(href);
                if (target) {
                    target.scrollIntoView({ behavior: 'smooth' });
                }
            }
        });
    });

    // Lazy loading images
    if ('IntersectionObserver' in window) {
        const imageObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const img = entry.target;
                    img.src = img.dataset.src;
                    img.classList.add('loaded');
                    imageObserver.unobserve(img);
                }
            });
        });

        document.querySelectorAll('img[data-src]').forEach(img => {
            imageObserver.observe(img);
        });
    }
});