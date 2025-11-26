window.addEventListener('DOMContentLoaded', event => {
    // Simple-DataTables
    // https://github.com/fiduswriter/Simple-DataTables/wiki

    const datatablesSimple = document.getElementById('datatablesSimple');
    if (datatablesSimple) {
        new simpleDatatables.DataTable(datatablesSimple, {
    searchable: true,        // activer/désactiver la recherche
    fixedHeight: true,        // hauteur fixe
    perPage: 5,               // nombre de lignes par page
    perPageSelect: [5, 10, 20], // options de pagination
    labels: {
        placeholder: "Rechercher...", // texte de recherche
        perPage: "{select} lignes par page",
        noRows: "Aucune donnée",
        info: "Affichage de {start} à {end} sur {rows} lignes"
    }
});

    }
});
