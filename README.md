# Liquor Store POS (Pure PHP MVC) — Local Docker + ESC/POS Text Receipts

Runs locally (offline-capable) using Docker: Nginx + PHP-FPM + MySQL.
Prints receipts silently via a local Print Bridge (Node + ESC/POS).

## Requirements
- Windows 10/11
- Docker Desktop (WSL2 enabled)
- Node.js (only for print bridge)

## Start POS (Local)
1) Copy `.env.example` to `.env`

2) Start containers:
```bash
docker compose up -d --build
```

3) Run DB migration (run once):
```bash
docker compose exec php php scripts/migrate.php
```

4) Open:
- POS: http://localhost:8080
- phpMyAdmin: http://localhost:8081

## Default Login
- Username: admin
- PIN: 1234

## Start Print Bridge (Silent printing)
```bash
cd print-bridge
npm install
npm start
```
Print bridge runs at: http://127.0.0.1:9123

## Notes
- USB barcode scanner acts like a keyboard.
- Receipt printer (USB): install Windows driver from seller.
- Cash drawer optional: RJ11 into printer; enable in Settings later.
