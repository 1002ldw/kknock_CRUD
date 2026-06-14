# kknock CRUD

Simple Apache/PHP/MySQL CRUD board.

## Runtime detection

This repository has no Composer manifest or explicit PHP platform constraint.
The source uses core PHP sessions, password hashing, and `mysqli`, with no syntax
requiring a newer release. PHP 8.3 is selected as the supported default because
it matches Ubuntu 24.04's PHP generation. The image installs the required
`mysqli` extension on the official Apache/PHP base.

Set `PHP_VERSION` in `.env` to use another compatible official PHP Apache image.

## Run with Docker Compose on Ubuntu

Install Docker Engine and the Compose plugin, then run:

```bash
cp .env.example .env
nano .env
docker compose up --build -d
docker compose ps
```

Open `http://<ubuntu-vm-ip>:8080`. From inside the VM, use
`http://localhost:8080`.

The initial login is read from `ADMIN_USERNAME` and `ADMIN_PASSWORD` in
`.env`. Change the example passwords before the first startup.

If a VM firewall is enabled, allow the configured application port:

```bash
sudo ufw allow 8080/tcp
```

## Database initialization

On startup, the `db-init` service:

1. Waits for MySQL to become healthy.
2. Creates any missing tables from `sql/schema.sql`.
3. Creates the configured initial user only when that username is absent.

MySQL data is stored in the `mysql_data` Docker volume. Changing admin
credentials in `.env` does not overwrite an existing user's password.

To perform a completely fresh initialization and delete all application data:

```bash
docker compose down -v
docker compose up --build -d
```

## Operations

```bash
docker compose logs -f
docker compose down
docker compose ps
```
