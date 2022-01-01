#!/bin/bash
set -e

psql -v ON_ERROR_STOP=1 --username "$POSTGRES_USER" --dbname "$POSTGRES_DB" <<-EOSQL
    CREATE USER "nature-quizzer" WITH LOGIN PASSWORD 'nature-quizzer';
    CREATE DATABASE "nature-quizzer";
    GRANT ALL PRIVILEGES ON DATABASE "nature-quizzer" TO "nature-quizzer";
    CREATE SCHEMA "web_nature_quizzer" AUTHORIZATION "nature-quizzer";
    CREATE SCHEMA "col" AUTHORIZATION "nature-quizzer";
EOSQL
