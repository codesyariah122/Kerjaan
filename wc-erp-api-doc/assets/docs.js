window.onload = () => {
    const ui = SwaggerUIBundle({
        url: window.location.origin + '/wp-json/erp/v1/schema', // contoh, bisa diganti ke file .json kamu
        dom_id: '#swagger-ui',
        presets: [SwaggerUIBundle.presets.apis, SwaggerUIStandalonePreset],
        layout: "BaseLayout",
    });
    window.ui = ui;
};
