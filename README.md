# 🍛 LankaEats – Sri Lankan Digital Recipe Book

**ICT 2209 – Web Technologies · Mini Project (Individual Submission)**
Faculty of Technology, Department of ICT – Rajarata University of Sri Lanka

LankaEats is an interactive recipe book of traditional Sri Lankan food. Visitors can browse,
search and filter recipes – rice & curry, kottu, hoppers, pittu, kokis, kavum, short eats and
drinks – without the page reloading. Registered members get a personal dashboard where they
can add, edit and delete their own recipes, with an optional photo.

> **Theme:** *Digital Recipe Book* (guide section 1) – recipe search, dynamic content loading
> and validated forms for user-submitted recipes, all with a Sri Lankan spice-box identity
> (cinnamon, turmeric and curry-leaf colours, hand-drawn SVG illustrations).

---

## ✨ Features mapped to the guide

| Guide requirement | How LankaEats meets it | Where |
|---|---|---|
| **2.1** At least three pages | Home, Recipes (features), Recipe details, About, Contact, Register, Login, Dashboard, Add/Edit recipe | root `*.php`, `auth/` |
| **2.1** Responsive | Bootstrap 5 grid + custom breakpoints (tested at 375 px, 768 px, 1200 px); navbar collapses into an animated hamburger menu | `css/style.css` §18 |
| **2.2** Dynamic content | Live search/filter, quick-view modal, FAQ toggles, show/hide method, ingredient checklist | `js/recipes.js`, `js/main.js` |
| **2.2** User input with validation | Register, login, contact, add/edit recipe – validated in JavaScript **and** PHP | `js/validation.js`, `includes/functions.php` |
| **2.3.1** Dynamic content updates | `fetch()` to `api/recipes.php` redraws results without reloading; FAQ filter & expand-all; checklist progress; show/hide steps | `js/recipes.js`, `js/main.js` |
| **2.3.2** Interactive image slider | Custom slider (not Bootstrap's carousel): auto-play with progress bar, prev/next, dots, keyboard ← →, touch swipe, pause on hover/focus | `index.php`, `js/main.js` |
| **2.3.3** Form validation | Declarative rules (`data-rule`, `required`, `data-match`…), inline Bootstrap errors while typing and on submit, live password-strength meter | `js/validation.js` |
| **2.3.4** Smooth scrolling | All in-page links scroll smoothly with a sticky-navbar offset; back-to-top button | `js/main.js` |
| **2.3.5** Event handling | Bootstrap tooltips, modals (quick view + delete confirmation), card tilt on hover, keyboard/touch events, copy-link & print buttons | `js/main.js`, `js/recipes.js` |
| **2.3.6** Custom animations | IntersectionObserver fade/slide-ins, count-up statistics, card pop-in, floating hero art, animated hamburger | `js/main.js`, `css/style.css` §17 |
| **2.4** Creativity & originality | Original recipe texts, hand-made SVG logo, hero, category icons and slider scenes; spice-inspired palette with Fraunces + Manrope fonts | `images/`, `database.sql` |
| **3.1** MySQL database | `lanka_eats` database (InnoDB, utf8mb4) | `database.sql` |
| **3.2** Required tables | `users` (id, username, email, password – hashed, created_at) + `categories`, `recipes`, `messages` | `database.sql` |
| **3.3** Registration | `password_hash()`, duplicate username/e-mail check, sticky fields | `auth/register.php` |
| **3.3** Login | Username **or** e-mail, `password_verify()`, `session_regenerate_id(true)`, redirect to dashboard, attempt limiter | `auth/login.php` |
| **3.3** Logout | CSRF-protected POST; clears `$_SESSION`, deletes the cookie, `session_destroy()` | `auth/logout.php` |
| **3.4** Contact form | Stored in `messages` (id, name, email, subject, message, created_at); PHPMailer stub included (disabled) | `contact.php` |
| **3.5** Frontend ↔ backend | Every form posts to PHP **after** JS validation passes; PHP validates again | all forms |
| **3.6** Database export | phpMyAdmin-style dump in the repository root | `database.sql` |

### Security & quality
- **PDO prepared statements** for every query (real prepares, emulation off)
- **`htmlspecialchars()`** on all output via the `e()` helper; API data escaped in JS before rendering
- **CSRF tokens** on every POST form (register, login, logout, contact, add/edit/delete recipe)
- **Owner checks** on edit/delete – both in PHP and inside the SQL `WHERE … AND user_id = ?`
- Secure uploads: real MIME type check (`finfo`), JPG/PNG/WEBP only, 2 MB limit, random file names, scripts blocked in `images/uploads/`
- Session hardening: HttpOnly + SameSite cookies, strict mode, ID regenerated on login
- Sticky form values after validation errors; Post/Redirect/Get with flash messages

---

## 🧰 Tech stack

| Layer | Technology |
|---|---|
| Frontend | HTML5, CSS3, **Bootstrap 5.3** (CDN), Bootstrap Icons, vanilla JavaScript (ES6, no jQuery) |
| Backend | **PHP 8** (no frameworks, no Composer) |
| Database | **MySQL / MariaDB** via PDO |
| Server | XAMPP (Apache + MySQL) |
| Fonts | Google Fonts – Fraunces (headings), Manrope (body) |

---

## 📁 Folder structure

```
lankaeats/
├── css/
│   └── style.css            # Custom theme on top of Bootstrap
├── js/
│   ├── main.js              # Slider, smooth scroll, tooltips, FAQ, animations
│   ├── validation.js        # Client-side validation for all forms
│   └── recipes.js           # Live search, quick-view modal, checklist
├── images/
│   ├── logo.svg, hero.svg, leaf.svg
│   ├── cat-*.svg            # Category illustrations (also recipe placeholders)
│   ├── slide-*.svg          # Slider scenes
│   └── uploads/             # User photos (git-ignored, keeps .gitkeep + .htaccess)
├── includes/
│   ├── config.php           # DB credentials + BASE_URL
│   ├── db.php               # PDO connection
│   ├── functions.php        # Helpers: escaping, validation, CSRF, auth, flash, queries, uploads
│   ├── header.php           # <head> + navbar + flash messages
│   ├── footer.php           # Footer, quick-view modal, scripts
│   ├── recipe_form.php      # Shared add/edit recipe form
│   └── delete_modal.php     # Delete confirmation modal
├── auth/
│   ├── register.php
│   ├── login.php
│   └── logout.php
├── api/
│   └── recipes.php          # JSON API used by fetch()
├── index.php                # Home
├── recipes.php              # Browse / search / filter
├── recipe.php               # Single recipe
├── about.php                # Story + FAQ
├── contact.php              # Contact form
├── dashboard.php            # Member dashboard
├── add_recipe.php
├── edit_recipe.php
├── delete_recipe.php
├── database.sql             # Database export
└── README.md
```

---

## 🚀 Setup (XAMPP)

1. **Install XAMPP** (PHP 8.0 or newer) from <https://www.apachefriends.org>.
2. **Copy the project folder** into XAMPP's web root so the path is
   `C:\xampp\htdocs\lankaeats\` (Windows) or `/Applications/XAMPP/htdocs/lankaeats/` (macOS).
3. Open the **XAMPP Control Panel** and **start Apache and MySQL**.
4. Open **phpMyAdmin** at <http://localhost/phpmyadmin>.
5. Click **Import** → **Choose file** → select `database.sql` → **Import / Go**.
   The script creates the `lanka_eats` database, all tables and the sample data by itself.
6. Visit **<http://localhost/lankaeats/>** 🎉

### Configuration
Settings live in `includes/config.php`:

```php
DB_HOST = '127.0.0.1'   DB_PORT = '3306'
DB_NAME = 'lanka_eats'  DB_USER = 'root'   DB_PASS = ''   // XAMPP default: empty password
BASE_URL = 'http://localhost/lankaeats/'                   // must end with a slash
```

- If your MySQL root user has a password, set `DB_PASS`.
- If you renamed the folder, update `BASE_URL`.
- To keep personal settings out of Git, create `includes/config.local.php` and `define()`
  the constants there – it is loaded first and ignored by Git.

### Troubleshooting
- **"We can't reach the kitchen" page** → MySQL isn't running, `database.sql` wasn't imported,
  or the credentials in `config.php` are wrong.
- **MySQL won't start / port 3306 busy** → another MySQL service is using the port. Stop it,
  or change XAMPP's MySQL port and set `DB_PORT` to match.
- **Images don't upload** → make sure `images/uploads/` is writable by Apache.

---

## 🔑 Demo login

| Username | E-mail | Password | Notes |
|---|---|---|---|
| `demo` | demo@lankaeats.lk | `Demo@123` | Owns 13 of the sample recipes |
| `kithul_kitchen` | kithul@lankaeats.lk | `Kithul@123` | Owns 3 recipes – use it to see that you **can't** edit another cook's recipes |

Passwords are stored only as `password_hash()` values in `database.sql`.

---

## 🗄️ Database overview

| Table | Purpose | Key columns |
|---|---|---|
| `users` | Registered members | `id`, `username` (unique), `email` (unique), `password` (hash), `created_at` |
| `categories` | 6 food categories | `id`, `name`, `slug`, `description` |
| `recipes` | Recipes (FK → users **ON DELETE CASCADE**, FK → categories) | `title`, `description`, `ingredients`, `instructions`, `prep_time`, `difficulty` (ENUM), `spice_level` (1–5), `is_vegetarian`, `image`, `is_featured`, `created_at` |
| `messages` | Contact form submissions | `id`, `name`, `email`, `subject`, `message`, `created_at` |

Seed data: 2 demo users, 6 categories, 16 original recipes, 1 sample message.

---

## 👤 Author

- **Course:** ICT 2209 – Web Technologies, Rajarata University of Sri Lanka

All recipe text and illustrations in this project are original work created for this assignment.
