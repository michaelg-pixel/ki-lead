# 📦 DOWNLOAD & UPLOAD ANLEITUNG

## 🎯 FTP Upload-Pfad:

```
/app.mehr-infos-jetzt.de/customer/course-view.php
```

Oder vollständiger Pfad:
```
/home/mehr-infos-jetzt-app/htdocs/app.mehr-infos-jetzt.de/customer/course-view.php
```

---

## 📥 DOWNLOAD:

**Die fertige Datei zum Download:**

https://raw.githubusercontent.com/michaelg-pixel/ki-lead/main/customer/course-view.php

**ABER ACHTUNG:** Diese Datei aus GitHub hat noch den ALTEN Code!

---

## ✅ BESTE LÖSUNG:

**Ich erstelle eine FERTIGE Datei mit KORRIGIERTEM CSS:**

1. Gehen Sie zu: 
   ```
   https://app.mehr-infos-jetzt.de/customer/download-fixed-course-view.php
   ```

2. Klicken Sie auf "Download course-view.php"

3. Laden Sie diese Datei per FTP hoch nach:
   ```
   /app.mehr-infos-jetzt.de/customer/course-view.php
   ```

---

## 🔧 WAS GEÄNDERT WURDE:

```css
/* VORHER */
overflow-x: auto;
overflow-y: hidden;
flex-wrap: nowrap;
scrollbar-width: none;

/* NACHHER */
overflow: hidden;    /* Kein Scrollbalken! */
flex-wrap: wrap;     /* Tabs wrappen automatisch */
```

---

## ⚠️ BACKUP:

**WICHTIG:** Sichern Sie zuerst die alte Datei!

Via FTP:
1. Laden Sie die aktuelle `course-view.php` herunter
2. Benennen Sie sie um in `course-view.php.backup`
3. Laden Sie dann die neue Datei hoch

---

**Ich erstelle jetzt das Download-Script...**
