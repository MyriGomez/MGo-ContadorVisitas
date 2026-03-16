//guardar métrica por url cargada
(function(){

if (!window.fetch) return;

document.addEventListener("DOMContentLoaded", function(){
    
    // 🔐 SECURITY: sessionStorage para deduplicar visitas en la misma sesión
    const post_id = contador_ajax.post_id;
    const storageKey = `mgo_v_${post_id}`;
    const today = new Date().toISOString().split('T')[0]; // formato '2026-03-16'
    
    // Si ya visitó esta página hoy en esta sesión, no contar de nuevo
    if (sessionStorage.getItem(storageKey) === today) {
        return;
    }

    // 🔐 SECURITY: Pequeño delay aleatorio para parecer más "humano"
    const randomDelay = Math.floor(Math.random() * 500);
    
    setTimeout(function() {
        fetch(contador_ajax.ajaxurl, {
            method: "POST",
            credentials: "same-origin",
            headers: {
                "Content-Type": "application/x-www-form-urlencoded"
            },
            body: new URLSearchParams({
                action: "registrar_visita",
                nonce: contador_ajax.nonce,
                id_post: post_id
            })
        })
        .then(() => {
            // Marcar como visitado tras éxito
            sessionStorage.setItem(storageKey, today);
        })
        .catch(err => {
            if (console && console.warn) {
                console.warn('MGo Contador: error al registrar visita', err);
            }
        });
    }, randomDelay);

});

})();


//guardar métrica por interacción
(function(){
let engagementSent = false;
let timer = null;

function enviarEngagement() {
    //si ya se guardó, no repite
    if (engagementSent) return;
    //si navegador no fetch, no guarda
    if (!window.fetch) return;
    
    // 🔐 SECURITY: Detectar navegadores automatizados (Selenium, Puppeteer, etc.)
    if (navigator.webdriver) {
        return;
    }
    
    //controla que se graba una métrica
    engagementSent = true;
    
    // 🔐 SECURITY: Pequeño delay aleatorio para parecer más "humano"
    const randomDelay = Math.floor(Math.random() * 300);
    
    setTimeout(function() {
        fetch(contador_ajax.ajaxurl, {
            method: "POST",
            credentials: "same-origin",
            headers: {
                "Content-Type": "application/x-www-form-urlencoded"
            },
            body: new URLSearchParams({
                action: "mg_engaged_visit",
                nonce: contador_ajax.nonce,
                id_post: contador_ajax.post_id
            })
        })
        .catch(err => {
            if (console && console.warn) {
                console.warn('MGo Contador: error al registrar engagement', err);
            }
        });
    }, randomDelay);
}

//métrica si pasan 7seg
timer = setTimeout(function(){
    enviarEngagement();
}, 7000);


//métrica si hace scroll
window.addEventListener("scroll", function(){
    if (engagementSent) return;
    enviarEngagement();
}, { once: true });


})();