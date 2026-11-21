# syntax=docker/dockerfile:1

# ---- 1. compilar la SPA
FROM node:22-alpine AS build
WORKDIR /app

COPY package.json package-lock.json ./
RUN npm ci

COPY . .

# Vite escribe estas direcciones dentro del código al compilar, por eso son argumentos de construcción
ARG VITE_API_URL=http://localhost:8000/api
ARG VITE_TURNSTILE_SITE_KEY=
ENV VITE_API_URL=$VITE_API_URL \
    VITE_TURNSTILE_SITE_KEY=$VITE_TURNSTILE_SITE_KEY

RUN npm run build-only

# ---- 2. servirla con Nginx (la configuración, con los dos sitios, la monta compose)
FROM nginx:stable-alpine
COPY --from=build /app/dist /usr/share/nginx/html
