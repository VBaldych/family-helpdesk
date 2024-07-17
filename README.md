[VBaldych LinkedIn](https://www.linkedin.com/in/volodymyr-baldych-322322183/)

# Family Helpdesk
Welcome to our Family Helpdesk, centralized hub for managing and resolving support requests efficiently and effectively.
Our Helpdesk System is designed to streamline customer support operations, ensuring that every query is addressed]
promptly and thoroughly.

Old issues (>30 days) will be deleted automatically from DB.

# Local installation
Be ensure that [Symfony](https://symfony.com/download) and [Docker](https://www.docker.com/products/docker-desktop/) are installed on your local machine
Then run commands in a CLI:
1. symfony server:start -d && docker-compose up -d
2. symfony console doctrine:database:create
3. symfony console doctrine:migrations:migrate

Admin credentials:
Login - admin@gmail.com
Password - admin

To run mailcatcher service run the command:
symfony open:local:webmail

# Want to help contribute?
Be sure to check out our repository with development tools on
<a target="_blank" href="https://github.com/VBaldych/home_helpdesk/">
Github</a>
