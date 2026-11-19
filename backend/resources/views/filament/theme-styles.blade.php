{{-- Finitions visuelles communes : fond clair et doux, cartes arrondies, ombres légères. Aucune couleur agressive. --}}
<style>
    /* Écrans d'accueil (connexion, mot de passe oublié, double authentification) */
    .fi-simple-layout {
        background:
            radial-gradient(60rem 30rem at 100% -10%, rgba(18, 59, 54, .08), transparent 60%),
            radial-gradient(40rem 24rem at -10% 110%, rgba(243, 201, 105, .14), transparent 60%),
            #f7f8f5;
    }
    .fi-simple-main {
        border-radius: 1.25rem !important;
        box-shadow: 0 1px 2px rgba(15, 23, 42, .04), 0 12px 32px -12px rgba(15, 23, 42, .12) !important;
        --tw-ring-color: rgba(15, 23, 42, .06) !important;
    }
    .fi-simple-header-heading { letter-spacing: -.01em; }

    /* Espace connecté : fond légèrement teinté, cartes et tableaux adoucis */
    .fi-body { background-color: #f7f8f5; }
    .fi-sidebar { background-color: #ffffff; }
    .fi-wi-stats-overview-stat, .fi-section, .fi-ta-ctn {
        border-radius: 1rem !important;
        box-shadow: 0 1px 2px rgba(15, 23, 42, .04), 0 6px 18px -10px rgba(15, 23, 42, .10) !important;
    }
    .fi-sidebar-item-active > a, .fi-sidebar-item-active > button { border-radius: .65rem; }
</style>
