import { App } from 'vue';
import TextLink from '@/components/TextLink.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Checkbox } from '@/components/ui/checkbox';
import { LoaderCircle } from 'lucide-vue-next';

export function registerGlobalComponents(app: App) {
    app.component('TextLink', TextLink);
    app.component('InputError', InputError);

    app.component('Button', Button);
    app.component('Input', Input);
    app.component('Label', Label);
    app.component('Checkbox', Checkbox);
    app.component('LoaderCircle', LoaderCircle);
}

export default {
    install: (app: App) => {
        registerGlobalComponents(app);
    }
};
