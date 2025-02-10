# Plugins Readme Updater

## 📌 Co je tento plugin?

Tento plugin automatizuje proces sledování aktualizací pluginů pro WordPress projekty v rámci SLA procesu. Umožňuje:

- **Export informací o pluginech na GitHub** pro dlouhodobé uchování a verzování.
- **Odesílání informací na Slack**, aby tým věděl, které pluginy je potřeba aktualizovat.
- **Double-check procesu** – Do threadu pod aktualizované pluginy se pošle informace o kontrolovaných věcech na webu.

🔗 *Více o celém SLA procesu najdete zde:* [PROVIZORNÍ ODKAZ]

---

## 📥 Instalace

1. Nahrajte plugin do složky `wp-content/plugins/`
2. Aktivujte ho v administraci WordPressu
3. Proveďte základní nastavení dle instrukcí níže

---

## ⚙️ Požadavky a nastavení

### 1️⃣ Nastavení prostředí (Environment)

Přejděte do **Environment Settings** a nastavte, zda se jedná o `staging` nebo `production` prostředí.

### 2️⃣ Nastavení Slack integrace

V **Slack Settings** nastavte:

- **Slack Webhook URL** (najdete v administraci Slack App)
- **Slack Channel ID** (najdete v detailu Slack kanálu)

**Slack App musí mít následující oprávnění:**

- `channels:history` – Pro čtení zpráv v kanálech
- `chat:write` – Pro odesílání zpráv
- `chat:write.public` – Pro odesílání zpráv i do kanálů, kde bot není členem
- `incoming-webhook` – Pro odesílání zpráv
- `reactions:write` – Pro přidávání a editaci emoji reakcí

### 3️⃣ Nastavení GitHub integrace

V **GitHub Settings** nastavte:

- **GitHub Repo** – Název repozitáře
- **GitHub Branch** – Branch, kam se commituje
- **GitHub Token** – Token uživatele s oprávněním commitu
- **GitHub Username** – Uživatelské jméno na GitHubu

---

## 🛠 Jak plugin používat

### 🏗 Na stagingu

1. **Nastavíte Reviewera** a případnou poznámku pro Slack zprávu v hlavním menu pluginu.
2. **Odešlete informaci o pluginech na Slack**, aby tým věděl, co se bude aktualizovat.
3. **Po kontrole webu** reviewer zapíše informace do Slack threadu.
4. **Commitnete změny na GitHub**, čímž se uloží aktuální seznam pluginů.

### 🚀 Na produkci

Na produkčním prostředí máte dostupnou pouze možnost **commitnout aktuální stav pluginů na GitHub**.

---

## 🔗 Další informace

Tento plugin je vyvíjen pro interní potřeby a optimalizaci SLA procesů. Pro více informací o celém workflow se podívejte na [PROVIZORNÍ ODKAZ].

