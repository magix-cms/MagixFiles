# MagixFiles - Gestionnaire de Documents & Audio pour Magix CMS 4

[![Release](https://img.shields.io/github/release/magix-cms/MagixFiles.svg)](https://github.com/magix-cms/MagixFiles/releases/latest)
[![License](https://img.shields.io/github/license/magix-cms/MagixFiles.svg)](LICENSE)
[![PHP Version](https://img.shields.io/badge/php-%3E%3D%208.2-blue.svg)](https://php.net/)
[![Magix CMS](https://img.shields.io/badge/Magix%20CMS-4.x-success.svg)](https://www.magix-cms.com/)

MagixFiles est un gestionnaire de fichiers avancé pour Magix CMS 4. Il permet d'attacher des documents (PDF, Word, Excel, ZIP) et des fichiers audio (MP3, WAV, OGG) à n'importe quel module natif du cœur (Produits, Pages, Actualités, Catégories, À propos).

Il intègre un système de restriction d'accès puissant pour vendre des fichiers numériques via `Magixcartpay`.

## 🚀 Fonctionnalités principales

* **Visibilité Conditionnelle (Public / Privé)** : Proposez des documents en téléchargement libre (Public) ou bloquez-les derrière un tunnel de vente (Privé).
* **Protection Physique (Anti-fuite)** : Les fichiers privés sont stockés dans un dossier protégé par un `.htaccess` généré automatiquement. Le téléchargement passe par un contrôleur PHP vérifiant les droits d'achat (Erreur 403 en cas d'accès illégitime).
* **Lecteur Audio Intégré & Streaming Optimisé** : Les fichiers MP3 génèrent un lecteur HTML5 natif. Le flux audio est servi via `HTTP 206 Partial Content` (lecture par petits paquets) pour supporter l'avance rapide (Safari/iOS) sans saturer la RAM du serveur, et libère instantanément la session PHP pour ne pas bloquer la navigation du client.
* **Compteur de Téléchargements** : Suivi précis du nombre de clics sur le bouton "Télécharger" (exclut intelligemment les requêtes de streaming audio).
* **Interface AJAX** : Upload drag & drop (jusqu'à 200 Mo), tri intuitif et renommage à la volée.

## 📦 Installation

1. Placez le dossier `MagixFiles` dans le répertoire `plugins/`.
2. Installez le plugin depuis l'administration.
3. *Note : Pour exploiter la fonctionnalité "Fichiers Privés", les plugins `Magixaccount` (comptes clients) et `Magixcartpay` (moteur e-commerce) doivent être installés et actifs.*

## 🎨 Intégration sur le site (Frontend)

L'affichage s'adapte automatiquement selon que le fichier est public (libre d'accès) ou privé (vendu avec un produit).

### 1. Fichiers Publics (Layout Dynamique)

Vous n'avez **rien à coder**. Si vous ajoutez des fichiers "Publics" à une page, un produit ou une actualité, le widget s'affichera automatiquement grâce au système de Layout Dynamique de Magix CMS via le hook standard :

```smarty
{* S'insère automatiquement via le gestionnaire de Layout *}
{hook name="displayProductExtraContent" id_product=$productData.id|default:0}
```

### 2. Vente de Fichiers (Zone Privée Sécurisée)

Pour les fichiers cochés "Privé" dans l'administration d'un produit, vous devez utiliser une zone conditionnelle dans votre thème (ex: `skin/votre_theme/catalog/product.tpl`).

Le widget générera automatiquement le lecteur audio ou les boutons de téléchargement de vos fichiers Premium **uniquement si la commande est payée** :

```smarty
{* 1. MagixCartpay vérifie si le client connecté possède ce produit *}
{has_purchased id_product=$productData.id|default:0 assign="user_owns_product"}

{if $user_owns_product}

    <div class="alert alert-success">
        <h5><i class="bi bi-unlock-fill"></i> Vos fichiers débloqués</h5>
        <p>Merci pour votre achat ! Voici vos accès :</p>
    </div>

    {* 2. Affiche UNIQUEMENT les fichiers MagixFiles cochés "Privé" *}
    {hook name="displayProductPrivateFiles" id_product=$productData.id|default:0}

{else}

    <div class="card bg-light border-0 p-4 text-center">
        <i class="bi bi-lock fs-1 text-muted mb-3"></i>
        <h5>Contenu Premium</h5>
        <p class="text-muted">Achetez ce produit pour débloquer les fichiers de travail et les audios.</p>

        {* 3. Affiche le bouton d'ajout au panier Magixcartpay *}
        {hook name="displayProductAddToCart" id_product=$productData.id|default:0}
    </div>

{/if}
```

## 🛠️ Extensions et Types de fichiers pris en charge

Le système embarque un validateur strict (vérification combinée de l'extension et du `finfo` MIME-Type) empêchant l'upload de scripts malveillants.

* **Documents** : `.pdf`, `.doc`, `.docx`, `.xls`, `.xlsx`, `.csv`, `.ppt`, `.pptx`, `.odt`, `.ods`
* **Archives** : `.zip`
* **Audio** : `.mp3`, `.wav`, `.ogg`

## 📄 Licence

Ce projet est sous licence **GPLv3**. Voir le fichier [LICENSE](LICENSE) pour plus de détails.
Copyright (C) 2008 - 2026 Gerits Aurelien (Magix CMS)