git add app/
git commit -m "Add application core (Controllers, Models, Providers)"

git add config/ database/
git commit -m "Add configurations and database migrations"

git add routes/
git commit -m "Add application routes"

git add resources/views/
git commit -m "Add blade views"

git add resources/js/components/
git commit -m "Add Vue components"

git add resources/js/views/
git commit -m "Add Vue page views"

git add resources/js/
git commit -m "Add Vue logic and state management"

git add public/ resources/css/
git commit -m "Add public assets and styles"

git add composer.json composer.lock package.json package-lock.json tailwind.config.js vite.config.js
git commit -m "Add configuration and dependency files"

git add .
git commit -m "Initial release and final touches"

git branch -M main
git push -u origin main
