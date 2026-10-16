{* ==========================================================================
   ZONE 1 : CONTENU PRIVÉ (Débloqué uniquement après achat via Magixcartpay)
   ========================================================================== *}

{* 1. On interroge Magixcartpay silencieusement *}
{has_purchased id_product=$product.id assign="user_owns_product"}

{* 2. On adapte l'affichage en fonction du résultat *}
{if $user_owns_product}

    <div class="alert alert-success">
        <h5><i class="bi bi-unlock-fill me-2"></i>Contenu débloqué</h5>
        <p class="mb-0">Merci pour votre achat ! Voici vos accès privés :</p>
    </div>

    {* A. Affiche UNIQUEMENT les vidéos Dailymotion cochées "Privée" dans l'admin *}
    {hook name="displayProductPrivateVideos" id_product=$product.id}

    {* B. Affiche UNIQUEMENT les fichiers MagixFiles (PDF, Word, Excel, MP3) cochés "Privé" dans l'admin *}
    {hook name="displayProductPrivateFiles" id_product=$product.id}

{else}

    {* --- L'UTILISATEUR N'A PAS ACHETÉ LE PRODUIT (Ou n'est pas connecté) --- *}
    <div class="card bg-light border-0 p-4 text-center my-4">
        <i class="bi bi-lock fs-1 text-muted mb-3"></i>
        <h5>Contenu Premium</h5>
        <p class="text-muted">Achetez ce produit pour débloquer la vidéo explicative et les fichiers de travail.</p>

        {* Bouton d'ajout au panier Magixcartpay *}
        {hook name="displayProductAddToCart" id_product=$product.id}
    </div>

{/if}


{* ==========================================================================
   ZONE 2 : CONTENU PUBLIC (Géré automatiquement par votre Layout Dynamique)
   ========================================================================== *}
{*
   Ici, votre hook de Layout existant affichera automatiquement :
   - Les fichiers MagixFiles cochés "Public" (ex: brochure PDF gratuite, extrait MP3)
   - Les vidéos MagixDailymotion cochées "Publique" (ex: bande-annonce / teaser)
   - Les textes MagixMultiText
*}
{hook name="displayProductExtraContent" id_product=$product.id}