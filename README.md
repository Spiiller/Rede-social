Jardim e alegria é uma rede social para compartilhar posts sobre plantas, com uploads de imagens, likes, comentários, saves e painel admin. Desenvolvido com PHP, MySQL, JavaScript, HTML/CSS para portfólio.

## Tecnologias
- **Backend**: PHP 8+, PDO MySQL, Sessões.
- **Frontend**: HTML5, JavaScript (Fetch/AJAX), CSS.
- **Banco**: MySQL (tabelas: users, posts, post_images, post_likes, post_saves, comments).

## Instalação
1. Clone: `git clone https://github.com/Spiiller/Rede-social`
2. Configure DB em `config.php` (use `.env` para segurança).
3. Importe `docs/database.sql` e `docs/schema.sql` no MySQL.
4. Torne `uploads/` writable: `chmod 777 uploads/`.
5. Rode: `php -S localhost:8000` ou use XAMPP.
6. Acesse: `index.html` (feed), `login.html` (auth).

## Funcionalidades
- **Autenticação**: Registro/login/logout (`auth.php`, BCRYPT).
- **Feed**: Posts com galeria, likes/comments/saves (`posts.php`, `index.html`).
- **Perfil**: Edição, saved posts (`profile.php`, `profile.html`).
- **Admin**: CRUD posts/imagens (`admin_posts.php`, `admin.html`).
- **Uploads**: Multi-imagens (`upload.php`).
- **Testes**: DB (`test_db.php`), permissões (`check_permissions.php`), AJAX (`ajax_test.php`).

## Demo
[jar](https://jardimealegria.blog)

## Aprendizados
- PHP seguro (PDO, sessões).
- JS interativo (debounce, galleries).
- Queries complexas (GROUP_CONCAT).
