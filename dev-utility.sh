#!/usr/bin/env bash

set -u

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$ROOT_DIR"

if command -v vendor/bin/sail >/dev/null 2>&1 && vendor/bin/sail artisan --version >/dev/null 2>&1; then
    RUNNER="sail"
else
    RUNNER="compose"
fi

run_service_cmd() {
    local cmd="$1"

    if [ "$RUNNER" = "sail" ]; then
        vendor/bin/sail $cmd
    else
        docker compose $cmd
    fi
}

run_artisan() {
    local cmd="$1"

    if [ "$RUNNER" = "sail" ]; then
        vendor/bin/sail artisan $cmd
    else
        docker compose exec app php artisan $cmd
    fi
}

run_composer() {
    local cmd="$1"

    if [ "$RUNNER" = "sail" ]; then
        vendor/bin/sail composer $cmd
    else
        docker compose exec app composer $cmd
    fi
}

run_npm() {
    local cmd="$1"

    if [ "$RUNNER" = "sail" ]; then
        vendor/bin/sail npm $cmd
    else
        docker compose exec app npm $cmd
    fi
}

pause() {
    printf "\nPress Enter to continue..."
    read -r
}

print_header() {
    clear
    printf "DrawingFlow Utility Menu\n"
    printf "Project: %s\n" "$ROOT_DIR"
    printf "Runner: %s\n\n" "$RUNNER"
}

run_custom_artisan() {
    printf "Enter artisan arguments (example: migrate --seed --no-interaction): "
    read -r args

    if [ -n "$args" ]; then
        run_artisan "$args"
    fi
}

run_custom_npm() {
    printf "Enter npm arguments (example: run dev): "
    read -r args

    if [ -n "$args" ]; then
        run_npm "$args"
    fi
}

while true; do
    print_header
    cat <<'MENU'
1) Start containers
2) Stop containers
3) Restart containers
4) Show container status
5) App shell
6) Composer install
7) App setup (composer setup)
8) Generate app key
9) Run migrations
10) Run migrations with seed
11) Fresh migrate and seed
12) Run all tests
13) Run feature tests
14) Run unit tests
15) Run lint
16) Run lint fix
17) Run static analysis
18) Frontend dev server
19) Frontend build
20) Frontend lint check
21) Frontend format check
22) Import legacy CSV data
23) Custom artisan command
24) Custom npm command
0) Exit
MENU

    printf "\nChoose an option: "
    read -r option

    case "$option" in
        1) run_service_cmd "up -d" ;;
        2) run_service_cmd "down" ;;
        3) run_service_cmd "down" && run_service_cmd "up -d" ;;
        4) run_service_cmd "ps" ;;
        5) docker compose exec app bash ;;
        6) run_composer "install" ;;
        7) run_composer "setup" ;;
        8) run_artisan "key:generate" ;;
        9) run_artisan "migrate --no-interaction" ;;
        10) run_artisan "migrate --seed --no-interaction" ;;
        11) run_artisan "migrate:fresh --seed --no-interaction" ;;
        12) run_artisan "test --compact" ;;
        13) run_artisan "test --compact --testsuite=Feature" ;;
        14) run_artisan "test --compact --testsuite=Unit" ;;
        15) run_composer "lint" ;;
        16) run_composer "lint:fix" ;;
        17) run_composer "analyse" ;;
        18) run_npm "run dev" ;;
        19) run_npm "run build" ;;
        20) run_npm "run lint:check" ;;
        21) run_npm "run format:check" ;;
        22) run_artisan "data:import-legacy-csv --no-interaction" ;;
        23) run_custom_artisan ;;
        24) run_custom_npm ;;
        0) exit 0 ;;
        *) printf "Invalid option\n" ;;
    esac

    pause
done
