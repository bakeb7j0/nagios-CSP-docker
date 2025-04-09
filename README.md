# Running the Fullstack Solution
The fullstack solution deploys both the `nagios-csp` and `mysql` containers.  

## Controlling Your Deployment
Your individual deployment likely requires some customizations, which are handled with environment variables.  The set of environment variables available are listed in the table below.
| Shell Env Variable    | Required | Purpose |
| --------------------- | ---------------------------- | ---------------------------------------------- |
| `DB_USER`             | required                     | the mysql username                             |
| `DB_PASSWORD`         | required                     | plaintext secret for `DB_USER`                 |
| `DB_NAME`             | required                     | name of mysql database used by nagios          |
| `MYSQL_ROOT_PASSWORD` | required                     | plaintext secret for root mysql user           |
| `NAGIOS_HOST_PORT`    | optional (default is `8080`) | External port used to access the nagios web ui |
| `MYSQL_HOST_PORT`     | optional (default is `3306`) | External port used to access mysql databases   |  

## Starting the Container Stack
First, set the required environment variables:
- `#> export DB_USER="nagios"`
- `#> export DB_PASSWORD="your witty password"`
- `#> export DB_NAME="nagios"`
- `#> export MYSQL_ROOT_PASSWORD="your witty and super secure passphrase"`

Optionally, change the exposed ports for Nagios and MySQL
- `#> export NAGIOS_HOST_PORT="8888"`
- `#> export NAGIOS_HOST_PORT="13306"`

Start the Stack
- `#> docker compose -f docker-compose_fullstack.yaml --env-file fullstack.env up -d`


