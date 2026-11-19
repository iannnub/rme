import { Activity } from 'lucide-react';
import { type ComponentProps } from 'react';

export default function AppLogoIcon(props: ComponentProps<typeof Activity>) {
    return <Activity {...props} />;
}
