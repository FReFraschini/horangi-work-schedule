# Ambiente di Sviluppo Docker per l'App Gestione Turni

Questo documento fornisce le istruzioni per configurare e avviare l'ambiente di sviluppo locale utilizzando Docker.

## Requisiti

- Docker
- Docker Compose

## Procedura di Primo Avvio

1.  **Copia il file di configurazione ambientale:**
    ```sh
    cp .env.example .env
    ```

2.  **Avvia i container Docker:**
    Questo comando costruirà le immagini (se non già presenti) e avvierà i servizi in background.
    ```sh
    docker compose up -d --build
    ```

3.  **Installa le dipendenze di Composer:**
    ```sh
    docker compose exec app composer install
    ```

4.  **Genera la chiave dell'applicazione:**
    ```sh
    docker compose exec app php artisan key:generate
    ```

5.  **Esegui le migrazioni e il seeder del database:**
    Questo comando creerà la struttura del database e l'utente "gestore" di default.
    ```sh
    docker compose exec app php artisan migrate:fresh --seed
    ```

6.  **Installa le dipendenze NPM e compila gli asset:**
    ```sh
    docker compose exec app npm install
    docker compose exec app npm run build
    ```

## Comandi Comuni

-   **Avviare l'ambiente:**
    ```sh
    docker compose up -d
    ```

-   **Fermare l'ambiente:**
    ```sh
    docker compose down
    ```

## Accesso all'Applicazione

Una volta completata la procedura di avvio, l'applicazione sarà accessibile all'indirizzo:
[http://localhost:8000](http://localhost:8000)

**Credenziali utente gestore di default:**
- **Email:** `gestore@example.com`
- **Password:** `password`
