# 🧠 SmartPromos – Module PrestaShop Intelligent pour Promotions Automatiques

## 🎯 Objectif général

**SmartPromos** est un module PrestaShop permettant d'analyser le comportement des clients et l'état du stock produits afin de **créer automatiquement des promotions ou des coupons personnalisés**, selon des **règles dynamiques configurables**.

Ce module vise à automatiser la fidélisation client et la gestion des promotions pour améliorer les ventes sans intervention manuelle.

---

## ⚙️ Fonctionnalités principales

### 1. Moteur de règles dynamiques
- Interface d’administration pour définir des conditions comme :
  - Montant total dépensé > X €
  - Nombre de commandes > N
  - Produit resté en stock > 30 jours
  - Catégorie ou produit peu vendu
- Actions configurables :
  - Générer un coupon de réduction
  - Appliquer une remise directe sur un produit ou une catégorie
  - Envoyer un email promotionnel au client

### 2. Génération automatique de coupons
- Création automatique via **CRON job** (exécution planifiée)
- Historique des coupons générés
- Limitation par client, produit ou durée

### 3. Notifications intelligentes
- Envoi automatique d’**emails personnalisés**
- Intégration optionnelle avec module Newsletter ou SMS
- Message dans le compte client (“Une nouvelle promotion vous attend !”)

### 4. Journalisation et sécurité
- Journal des actions automatiques (date, client, produit, type de promotion)
- Contrôle total depuis le back-office

### 5. Tableau de bord analytique (facultatif)
- Statistiques sur les promotions générées, coupons utilisés, et clients récurrents

---

## 🧩 Structure technique du module


---

## 🧰 Installation

### 🔹 Méthode 1 – via le back-office
1. Créez un dossier nommé `smartpromos` dans `/modules/`.
2. Placez tous les fichiers du module à l’intérieur.
3. Compactez le dossier en `.zip`.
4. Depuis votre **back-office PrestaShop**, allez à :  
   `Modules > Module Manager > Upload a Module`
5. Sélectionnez votre fichier `smartpromos.zip`.
6. Cliquez sur **Installer** ✅

### 🔹 Méthode 2 – manuelle
1. Copiez le dossier `smartpromos/` dans `modules/`.
2. Allez à `Modules > Module Manager`.
3. Recherchez "SmartPromos" et cliquez sur **Installer**.

---

## 🚀 Utilisation

1. Rendez-vous dans le menu d’administration du module :  
   **Modules > SmartPromos > Configuration**
2. Créez vos **règles dynamiques** :
   - Exemple : “Si stock > 50 et ventes < 10 → appliquer une remise de 15%.”
3. Activez le moteur automatique pour exécuter les règles via **CRON job**.
4. Consultez l’historique des actions dans la section “Logs”.

---

## 💡 Exemples concrets

| Scénario | Condition | Action |
|-----------|------------|--------|
| 🧤 Stock ancien | Produit en stock depuis 45 jours sans ventes | -20% sur le produit |
| 🛍️ Client fidèle | +5 commandes et +500€ dépensés | Coupon -10€ envoyé automatiquement |
| 🛒 Panier abandonné | Produit non acheté après 3 jours | Coupon -5% + email de relance |

---

## 🧠 Fonctionnement interne

Le module dispose d’un **moteur de règles (Rule Engine)** qui :
- Parcourt les règles actives ;
- Vérifie les conditions pour chaque produit/client ;
- Exécute les actions correspondantes :
  - Création de coupon,
  - Application de remise,
  - Envoi d’un email,
  - Notification interne.

Ce moteur peut être exécuté :
- **Automatiquement via CRON** (ex : chaque nuit)
- **Ou manuellement** depuis le back-office.

---

## 🧾 Journalisation & Sécurité

- Toutes les actions automatiques sont loguées dans une table SQL (`ps_smartpromos_rules_log`)
- Vous pouvez consulter la date, le type d’action, et le client/produit concerné.
- Le module respecte les standards PrestaShop et ne modifie pas les tables natives.

---

## 👩‍💻 Auteur

- **Nom :** Aya Aziz  
- **Version :** 1.0.0  
- **Compatibilité :** PrestaShop 1.7.x – 8.x  
- **Licence :** Open Source (Educational Use)

---

## 🧭 Feuille de route (Roadmap du stage)

| Jour | Tâche principale |
|------|------------------|
| 1️⃣ | Création de la structure du module |
| 2️⃣ | Interface de configuration (Back-office) |
| 3️⃣ | Base de données des règles |
| 4️⃣ | Développement du moteur de règles |
| 5️⃣ | Génération automatique de coupons |
| 6️⃣ | Notifications (email / Prestas
---

## 🧷 Licence
Projet éducatif – Non commercial.  
© 2025 Aya Aziz – Tous droits réservés.
