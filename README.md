# ShutterButton

Symcon Modul zur Steuerung von Rollläden über Taster (z. B. SODA S8 Griff).  
Unterscheidet zwischen kurzem und langem Tastendruck und steuert entsprechend die Rollladenbewegung.

![Version](https://img.shields.io/badge/version-1.0-blue.svg)
![Symcon](https://img.shields.io/badge/Symcon-7.1+-green.svg)
[![Symcon PHP SDK](https://img.shields.io/badge/Symcon-PHP%20Modul-orange)](https://www.symcon.de/service/dokumentation/entwicklerbereich/sdk-tools/sdk-php/)
[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](LICENSE)

---

## 📑 Inhaltsverzeichnis

1. [Funktionsumfang](#1-funktionsumfang)  
2. [Voraussetzungen](#2-voraussetzungen)  
3. [Installation](#3-installation)  
4. [Konfiguration](#4-konfiguration)  
5. [Statusvariablen](#5-statusvariablen)  
6. [Funktionsweise](#6-funktionsweise)  
7. [Hinweise](#7-hinweise)  

---

## 1. Funktionsumfang

- Unterscheidung zwischen kurzem und langem Tastendruck
- Steuerung von Rollläden über:
  - Positionsvariable (Integer)
  - Bewegungsvariable (String, z. B. OPEN/CLOSE/STOP)
- Unterstützung verschiedener Geräte-Logiken
- Konfigurierbare Dauer für kurzen Tastendruck
- Konfigurierbare Positionslogik:
  - 0 = offen / 100 = geschlossen
  - 0 = geschlossen / 100 = offen
- Debug-Ausgaben zur Analyse
- Anzeige der letzten Aktion und Tastdauer

---

## 2. Voraussetzungen

- Symcon ab Version 7.1  
- Kompatible Variablen:
  - Button (Boolean oder Enum → pressed/released)
  - Shutter Bewegung (String, z. B. OPEN/CLOSE/STOP)
  - Shutter Position (Integer, 0–100)

Weitere Infos:  
https://www.symcon.de/service/dokumentation/entwicklerbereich/sdk-tools/sdk-php/

---

## 3. Installation

### Über Module Control

https://github.com/Burki24/IPS_ShutterButtonControl


---

## 4. Konfiguration

| Einstellung | Beschreibung |
|------------|-------------|
| Button Variable | Taster (pressed / released) |
| Shutter Bewegung | Variable für OPEN / CLOSE / STOP |
| Shutter Position | Variable für Positionssteuerung |
| Richtung | Hoch oder Runter |
| kurzer Tastendruck (ms) | Schwelle zwischen kurz/lang |
| Positionslogik | Zuordnung von 0 und 100 |

---

## 5. Statusvariablen

| Name | Typ | Beschreibung |
|------|-----|-------------|
| LastDuration | Integer | Dauer des letzten Tastendrucks (ms) |
| LastAction | String | Erkannte Aktion (ShortPress / LongPress) |

---

## 6. Funktionsweise

### Kurzer Tastendruck

- Setzt die Position des Rollladens:
  - Hoch → z. B. 100
  - Runter → z. B. 0
- Abhängig von der eingestellten Positionslogik

---

### Langer Tastendruck

- Startet die Bewegung:
  - OPEN oder CLOSE
- Beim Loslassen:
  - STOP (falls unterstützt)

---

## 7. Hinweise

- Die verwendeten Werte (`OPEN`, `CLOSE`, `STOP`) sind gerätespezifisch  
- Nicht alle Geräte unterstützen `STOP`  
- Positionswerte können je nach System invertiert sein  
- Debug-Ausgaben helfen bei der Analyse  

---

## 🔧 Entwicklung & SDK

Dieses Modul basiert auf dem offiziellen  
[Symcon PHP SDK](https://www.symcon.de/service/dokumentation/entwicklerbereich/sdk-tools/sdk-php/)

---

## 📄 Lizenz

Dieses Projekt steht unter der MIT License.
