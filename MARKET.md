# Mise sur le market Jeedom — Checklist

## Prérequis comptes

- [ ] Compte développeur sur **market.jeedom.com**
- [ ] Dépôt GitHub public ou privé : `mickadam29/EventTranslator`
- [ ] Accès au panel développeur : market.jeedom.com > Mon compte > Mes plugins

---

## Structure de fichiers requise

```
EventTranslator/
├── plugin_info/
│   ├── info.json                  ✅ présent
│   ├── install.php                ✅ présent
│   └── EventTranslator_icon.png   ⚠️  MANQUANT — obligatoire
├── core/
│   ├── ajax/EventTranslator.ajax.php   ✅
│   ├── class/EventTranslator.class.php ✅
│   └── i18n/                          ⚠️  recommandé (traductions)
├── desktop/
│   ├── js/EventTranslator.js     ✅
│   └── php/EventTranslator.php   ✅
├── docs/
│   └── fr_FR/
│       ├── index.md              ✅ présent
│       └── changelog.md          ⚠️  MANQUANT — obligatoire
└── README.md                     ✅ présent
```

---

## Avant la soumission

### 1. Icône du plugin (obligatoire)

Créer `plugin_info/EventTranslator_icon.png` :
- Format : PNG avec fond transparent
- Taille : 65×65 px minimum (recommandé : 200×200 px, le market redimensionne)
- Le nom doit suivre le format `<id>_icon.png`

### 2. Changelog (obligatoire)

Créer `docs/fr_FR/changelog.md` :

```markdown
# Changelog EventTranslator

## v1.0.0 (YYYY-MM-DD)
- Version initiale
- Ajout par sélection d'équipement source
- Mode apprentissage (détection automatique des valeurs)
- Règles : Valeur, Commande, Scénario
- Répétition d'événements forcée (même valeur)
```

### 3. Translations i18n (recommandé)

Créer `core/i18n/fr_FR.json` avec les chaînes utilisées dans le code PHP/JS entre `{{...}}` :

```json
{
    "Ajouter": "Ajouter",
    "Configuration": "Configuration",
    "Retour": "Retour",
    "Apprendre": "Apprendre",
    "Terminer": "Terminer",
    "Sauvegarder": "Sauvegarder",
    "Supprimer": "Supprimer"
}
```

### 4. Vérifier info.json

```json
{
    "id": "EventTranslator",
    "name": "EventTranslator",
    "description": "...",
    "licence": "proprietary",
    "author": "mickadam29",
    "require": "4.2",
    "category": "programming",
    "changelog": "docs/fr_FR/changelog.md",
    "documentation": "docs/fr_FR/index.md"
}
```

Mettre à jour les champs `changelog` et `documentation` avec les chemins corrects.

### 5. Version

Ajouter un champ `"version"` dans `info.json` :
```json
"version": "1.0.0"
```

---

## Soumission sur le market

1. Aller sur **market.jeedom.com > Mon compte > Mes plugins > Ajouter un plugin**
2. Renseigner :
   - Nom : EventTranslator
   - ID : EventTranslator
   - Dépôt GitHub : `mickadam29/EventTranslator`
   - Branche : `main`
   - Prix : selon choix (gratuit / payant)
   - Catégorie : Programmation
3. Uploader l'icône
4. Rédiger la description courte et longue (texte marketing)
5. Lier la documentation (`docs/fr_FR/index.md`)
6. Soumettre pour validation par l'équipe Jeedom

---

## Processus de mise à jour

Pour chaque nouvelle version :
1. Mettre à jour `version` dans `info.json`
2. Ajouter l'entrée dans `docs/fr_FR/changelog.md`
3. `git tag v<version> && git push --tags`
4. Sur le market : déclencher la mise à jour depuis le panel développeur

---

## Notes importantes

- Le market Jeedom valide manuellement les plugins avant publication (délai variable)
- Pour un plugin payant, le market prélève une commission sur chaque vente
- Le champ `require` dans `info.json` définit la version Jeedom minimale supportée
- Tester sur une installation Jeedom propre avant soumission
