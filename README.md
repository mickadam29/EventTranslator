# EventTranslator

Plugin Jeedom permettant de traduire en temps réel les valeurs d'une commande info d'un équipement source, avec une table de correspondance personnalisée. Chaque correspondance peut mettre à jour une commande virtuelle, déclencher une action sur un autre équipement ou lancer un scénario.

## Fonctionnement

### 1. Ajout d'un équipement source

Dans le plugin, cliquer sur **Ajouter un équipement** puis sélectionner l'équipement Jeedom source au format `Objet > Équipement`.

Un équipement virtuel est automatiquement créé dans le plugin **Virtuel**, nommé `<Nom_source>_virt`.

> Un équipement source ne peut être ajouté qu'une seule fois dans le plugin.

### 2. Sélection des commandes à surveiller

Une fois l'équipement ajouté, sélectionner les commandes info à surveiller au format `Objet > Équipement > Commande`.

Pour chaque commande sélectionnée :
- Saisir un **nom libre** pour identifier la commande (utilisé comme nom de la commande virtuelle)
- Une commande info est créée dans l'équipement `<Nom_source>_virt`, en conservant le même type que la commande source

### 3. Mapping des valeurs

Pour chaque commande surveillée, définir autant de règles de correspondance que nécessaire. Chaque règle associe une **valeur source** à une **action cible** de l'un des trois types suivants :

#### Type `Valeur` — mise à jour de la commande virtuelle

| Valeur source | Type   | Valeur cible |
|---------------|--------|--------------|
| ON            | Valeur | MARCHE       |
| OFF           | Valeur | ARRET        |

La commande info de l'équipement virtuel est mise à jour avec la valeur cible.

#### Type `Commande` — déclenchement d'une action directe

| Valeur source | Type     | Commande cible                  |
|---------------|----------|---------------------------------|
| ON            | Commande | `Objet > Équipement > Allumer`  |
| OFF           | Commande | `Objet > Équipement > Éteindre` |

L'action sélectionnée est exécutée directement, sans passer par un scénario.

#### Type `Scénario` — lancement d'un scénario

| Valeur source | Type     | Scénario cible |
|---------------|----------|----------------|
| ON            | Scénario | `Lumière nuit` |

Le scénario sélectionné est lancé immédiatement.

> Plusieurs règles peuvent coexister pour la même valeur source (ex : mettre à jour la commande virtuelle ET déclencher une action).

### 4. Écoute en temps réel

Dès qu'une valeur de commande source change, toutes les règles correspondantes sont **immédiatement évaluées** :
- Les commandes virtuelles sont mises à jour
- Les actions sont exécutées
- Les scénarios sont lancés

## Dépendances

- Plugin **Virtuel** (obligatoire)
