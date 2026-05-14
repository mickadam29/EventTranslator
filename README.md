# EventTranslator

Plugin Jeedom qui traduit en temps réel les valeurs d'une commande info source vers des actions Jeedom configurables : mise à jour d'une commande info, déclenchement d'une commande action ou lancement d'un scénario.

Conçu notamment pour intégrer des claviers, interrupteurs et capteurs dont les valeurs brutes (ex. `single_left`, `long_right`, `1003`) doivent être converties en actions lisibles et exploitables dans l'automatisation Jeedom.

---

## Fonctionnement général

Pour chaque équipement source, EventTranslator crée un unique équipement `_et` contenant des commandes info natives Jeedom. Ces commandes sont directement utilisables dans les scénarios, les autres plugins et le dashboard.

```
Équipement source  →  EventTranslator _et  →  Scénario / Action / Commande
  (z2m, zwave…)         (commandes info)
```

Aucun plugin intermédiaire requis.

---

## Installation

Depuis le market Jeedom : rechercher **EventTranslator**, installer et activer le plugin.

---

## Guide d'utilisation

### 1. Ajouter un équipement source

1. Ouvrir le plugin EventTranslator
2. Cliquer sur **Ajouter** (bouton vert +)
3. Sélectionner l'équipement source dans la liste Jeedom (`Objet > Équipement`)
4. Un équipement `<Nom_source>_et` est créé automatiquement

> Un même équipement source ne peut être ajouté qu'une seule fois.

---

### 2. Ajouter des commandes à surveiller

Dans l'onglet **Commandes** de l'équipement, cliquer sur **Ajouter une commande** et sélectionner la commande info source à surveiller (ex. `action`, `scene`, `event`).

Pour chaque commande ajoutée :
- **Nom** : nom libre donné à la commande info produite dans l'équipement `_et`
- **Type** : type de la valeur produite — Texte, Numérique ou Binaire

---

### 3. Définir les règles de traduction

Chaque commande dispose d'un tableau de règles. Chaque règle associe une **valeur source** à une **action cible**.

#### Type `Valeur` — mise à jour de la commande info

La commande info de l'équipement `_et` est mise à jour avec la valeur cible. Utilisable dans un scénario via `triggerValue()` ou en condition.

| Valeur source | Type   | Valeur cible     |
|---------------|--------|------------------|
| single_left   | Valeur | appui gauche     |
| double_left   | Valeur | double gauche    |
| long_right    | Valeur | maintien droit   |

#### Type `Commande` — exécution directe d'une action

La commande action sélectionnée est exécutée immédiatement, sans scénario.

| Valeur source | Type     | Commande cible                    |
|---------------|----------|-----------------------------------|
| single_left   | Commande | `Salon > Lampe > Allumer`         |
| single_right  | Commande | `Salon > Lampe > Éteindre`        |

#### Type `Scénario` — lancement d'un scénario

Le scénario sélectionné est lancé immédiatement.

| Valeur source | Type     | Scénario cible   |
|---------------|----------|------------------|
| single_left   | Scénario | `Gestion lumière`|

> Plusieurs règles peuvent coexister pour la même valeur source (ex. mettre à jour une commande ET lancer un scénario).

---

### 4. Mode apprentissage

Le mode apprentissage permet de découvrir automatiquement les valeurs possibles d'une commande source sans les connaître à l'avance.

1. Cliquer sur **Apprendre** (bouton vert, icône casque) dans le panneau de la commande
2. Le bouton passe en rouge **Terminer (30s)** — le plugin écoute la commande source
3. Réaliser une action sur l'équipement physique (appui bouton, changement d'état…)
4. La valeur détectée est automatiquement ajoutée comme règle pré-remplie
5. Recommencer pour capturer d'autres valeurs — le compte à rebours repart à 30s à chaque nouvelle valeur
6. Cliquer sur **Terminer** ou attendre l'expiration pour arrêter l'écoute

Compléter ensuite le **Type** et la **Cible** pour chaque règle apprise.

> Les valeurs déjà présentes dans le tableau ne sont pas dupliquées.

L'ajout manuel reste disponible via **Ajouter une règle** à tout moment.

---

### 5. Répétition des événements

Les commandes info produites par EventTranslator sont configurées pour se déclencher **même si la valeur ne change pas** entre deux appuis. Ainsi, appuyer deux fois de suite sur le même bouton déclenchera bien deux fois le scénario ou l'action associée.

---

### 6. Écoute en temps réel

Dès qu'une valeur de commande source change, les règles correspondantes sont évaluées immédiatement :
- Les commandes info `_et` sont mises à jour (et leurs événements propagés)
- Les commandes action sont exécutées
- Les scénarios sont lancés

Les listeners sont reconstruits automatiquement à chaque sauvegarde ou activation d'un équipement EventTranslator.

---

## Architecture technique

- **1 source → 1 équipement `_et`** : pas de plugin Virtuel requis
- Les commandes info de l'équipement `_et` sont des commandes Jeedom natives visibles dans tous les sélecteurs (scénarios, autres plugins, dashboard)
- La détection des valeurs source repose sur le système de listeners Jeedom (`listener::byClass`)
- `repeatEventManagement = always` est forcé sur chaque commande ET pour garantir la propagation même en cas de valeur identique

---

## Dépendances

Aucune dépendance de plugin. Requiert **Jeedom 4.2** minimum.

---

## Licence

Propriétaire — tous droits réservés. Voir conditions d'utilisation sur le market Jeedom.
