<?php
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: no-referrer');
header("Content-Security-Policy: default-src 'self'; script-src 'self'; style-src 'self'; img-src 'self' blob: data:; connect-src 'self'; object-src 'none'; base-uri 'self'; form-action 'self'; frame-ancestors 'none'");
?><!doctype html>
<html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover"><meta name="theme-color" content="#233ae8"><meta name="apple-mobile-web-app-capable" content="yes"><meta name="apple-mobile-web-app-title" content="Mi armario"><title>Mi armario</title><link rel="icon" href="icon.svg" type="image/svg+xml"><link rel="apple-touch-icon" href="icon-192.png"><link rel="manifest" href="manifest.webmanifest"><link rel="stylesheet" href="app.css"><script src="app.js" type="module"></script></head><body><div id="app"><p class="boot">Abriendo tu armario…</p></div><dialog id="modal" aria-labelledby="modal-title"></dialog><div id="toast" role="status" aria-live="polite"></div></body></html>
