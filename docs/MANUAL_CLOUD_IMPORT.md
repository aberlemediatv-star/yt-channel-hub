# Handbuch: Cloud-Import (Google Drive, Dropbox, S3)

Staff können auf der Seite **Video hochladen** (`/staff/upload.php`) Videos aus **Google Drive**, **Dropbox** oder (optional) **S3** auswählen. Dafür sind **eigene OAuth-Apps** nötig (nicht dieselbe Client-ID wie für den YouTube-Kanal-OAuth).

---

## 1. Redirect-URIs (exakt eintragen)

Die Redirect-URL setzt sich aus **`APP_URL`** in `laravel/.env` (ohne abschließenden Schrägstrich) plus festem Pfad zusammen. **`APP_URL` muss mit der öffentlich erreichbaren Basis-URL identisch sein**, sonst schlägt OAuth fehl.

### Google Drive (Google Cloud Console)

**Authorized redirect URI:**

```
{APP_URL}/staff/oauth/gdrive/callback
```

**Beispiele** (nur zur Orientierung — Ihre Domain ersetzt `ihre-domain.tld`):

- `https://ihre-domain.tld/staff/oauth/gdrive/callback`
- Wenn die App unter einem Unterpfad läuft und `APP_URL` diesen enthält:  
  `https://ihre-domain.tld/app/staff/oauth/gdrive/callback`

### Dropbox (App Console)

**Redirect URI:**

```
{APP_URL}/staff/oauth/dropbox/callback
```

**Beispiel:**

- `https://ihre-domain.tld/staff/oauth/dropbox/callback`

> **Hinweis:** Dropbox-Apps mit **Scoped Access** benötigen die Scopes `files.metadata.read` und `files.content.read` (werden von der Anwendung angefragt).

---

## 2. Umgebungsvariablen (`laravel/.env`)

Siehe auch `laravel/.env.example` (Abschnitt Cloud-Import).

| Zweck | Variablen |
|--------|-----------|
| Google Drive OAuth | `CLOUD_IMPORT_GDRIVE_CLIENT_ID`, `CLOUD_IMPORT_GDRIVE_CLIENT_SECRET` |
| Dropbox OAuth | `CLOUD_IMPORT_DROPBOX_APP_KEY`, `CLOUD_IMPORT_DROPBOX_APP_SECRET` |
| S3 (optional) | `CLOUD_IMPORT_S3_ENABLED=true`, `CLOUD_IMPORT_S3_*` bzw. geteilte `AWS_*`-Keys |

In der **Google Cloud Console** die **Google Drive API** aktivieren und eine **OAuth-Client-ID (Webanwendung)** anlegen; dort die Redirect-URI aus Abschnitt 1 eintragen.

---

## 3. Bedienung im Staff-Bereich

1. Mit Staff-Account anmelden, Kanal wählen, **Video hochladen** öffnen.
2. **Google Drive** / **Dropbox**: zuerst **Verbinden**, dann **Liste laden**.
3. **Ordner:** Unterordner erscheinen als Schaltflächen; **Eine Ebene höher** springt zum übergeordneten Ordner.
4. Im Dropdown ein **Video** wählen, Titel und ggf. Metadaten ausfüllen, **Hochladen**.

Die OAuth-**Refresh-Tokens** werden verschlüsselt in der Tabelle `social_settings` gespeichert (Keys `cloud.gdrive.refresh_token`, `cloud.dropbox.refresh_token`).

---

## 4. Technische Endpunkte (Referenz)

| Aktion | Methode & Pfad |
|--------|----------------|
| Google OAuth Start | `GET /staff/oauth/gdrive/start?channel_id=…` |
| Google OAuth Callback | `GET /staff/oauth/gdrive/callback` |
| Dropbox OAuth Start | `GET /staff/oauth/dropbox/start?channel_id=…` |
| Dropbox OAuth Callback | `GET /staff/oauth/dropbox/callback` |
| Dateiliste (JSON) | `GET /staff/cloud/files?channel_id=…&provider=gdrive|dropbox|s3` |
| Drive: Ordner | `…&provider=gdrive&folder=root` oder `&folder=<Ordner-ID>` |
| Dropbox: Pfad | `…&provider=dropbox&path=` (Wurzel) oder `&path=/Unterordner` |

---

## 5. Grenzen & Betrieb

- **Große Dateien:** Download und YouTube-Upload laufen im gleichen HTTP-Request; bei sehr großen Dateien Timeouts oder hohe RAM-Last auf dem Server möglich — ggf. später Queue/Worker einplanen.
- **Dropbox:** `list_folder` ist nicht rekursiv; Navigation erfolgt Ordner für Ordner.
- **S3:** Es werden Objekt-Keys mit gängigen Video-Endungen unter optionalem Prefix gelistet.

Bei Umstellung der öffentlichen URL (`APP_URL`) müssen die Redirect-URIs in **Google** und **Dropbox** angepasst und die Verbindungen ggf. neu autorisiert werden.
