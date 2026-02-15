/**
 * @var {Object} page
 *
 * @property {string} page.url_edit
 */
const widget_openai_assistant_form = new class {

    init() {
        this.form = document.getElementById('widget-dialogue-form');
        this.service_field = document.querySelector('[name="service"]');
        this.endpoint_field = document.querySelector('[name="endpoint"]');
        
        if (this.service_field) {
            this.service_field.addEventListener('change', () => this.updateEndpointField());
            this.updateEndpointField();
        }
    }

    updateEndpointField() {
        const service = this.service_field.value;
        
        if (service == 0) { // OpenAI
            this.endpoint_field.value = 'https://api.openai.com/v1/chat/completions';
            this.endpoint_field.disabled = false;
        } else { // Custom
            this.endpoint_field.disabled = false;
        }
    }
}

