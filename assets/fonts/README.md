# Fonts Verzeichnis

Dieses Verzeichnis enthält alle lokalen Schriftarten für das KI Leadsystem.

## 📦 Installation erforderlich!

Die Font-Dateien sind **NICHT** im Git-Repository enthalten (zu groß).

### So installierst du die Fonts:

#### Option 1: Automatisch (empfohlen)
```bash
cd /home/u123456789/domains/app.mehr-infos-jetzt.de/public_html
chmod +x scripts/download-fonts.sh
./scripts/download-fonts.sh
```

#### Option 2: Manuell
Siehe: [`docs/FONT-INSTALLATION.md`](../../docs/FONT-INSTALLATION.md)

---

## ✅ Nach der Installation

Nach erfolgreicher Installation sollten hier **44 .woff2 Dateien** sein:

```
assets/fonts/
├── inter-300.woff2
├── inter-400.woff2
├── inter-500.woff2
├── inter-600.woff2
├── inter-700.woff2
├── inter-800.woff2
├── poppins-300.woff2
├── poppins-400.woff2
├── poppins-500.woff2
├── poppins-600.woff2
├── poppins-700.woff2
├── poppins-800.woff2
├── roboto-300.woff2
├── roboto-400.woff2
├── roboto-500.woff2
├── roboto-700.woff2
├── roboto-900.woff2
├── montserrat-300.woff2
├── montserrat-400.woff2
├── montserrat-500.woff2
├── montserrat-600.woff2
├── montserrat-700.woff2
├── montserrat-800.woff2
├── playfair-400.woff2
├── playfair-500.woff2
├── playfair-600.woff2
├── playfair-700.woff2
├── playfair-800.woff2
├── opensans-300.woff2
├── opensans-400.woff2
├── opensans-500.woff2
├── opensans-600.woff2
├── opensans-700.woff2
├── opensans-800.woff2
├── lato-300.woff2
├── lato-400.woff2
├── lato-700.woff2
└── lato-900.woff2
```

**Gesamtgröße:** ~2-3 MB

---

## 🔒 DSGVO-Konformität

✅ Alle Fonts auf eigenem Server  
✅ Keine externe Verbindung  
✅ Vollständige Kontrolle

---

## 🛠️ Troubleshooting

**Fonts werden nicht geladen?**

1. Überprüfe ob die Dateien existieren:
   ```bash
   ls -lh /home/u*/domains/*/public_html/assets/fonts/*.woff2
   ```

2. Überprüfe die Rechte:
   ```bash
   chmod 644 assets/fonts/*.woff2
   ```

3. Leere Browser-Cache (Strg+Shift+R)

4. Überprüfe in Browser DevTools → Network → Filter "font"
