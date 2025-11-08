import { Head, usePage } from '@inertiajs/react';
import SectionLayout from '@/Layouts/SectionLayout';
import { Card, CardHeader, CardTitle, CardContent } from '@/Components/ui/Card';

export default function Placeholder({ message }) {
    const { url } = usePage().props;
    // Determine section based on URL
    const isCompliance = url && (url.includes('/compliance') || url.includes('compliance'));
    
    if (isCompliance) {
        return (
            <SectionLayout sectionName="Compliance">
                <Head title="Coming Soon" />
                <div className="max-w-4xl mx-auto">
                    <Card>
                        <CardHeader>
                            <CardTitle>Coming Soon</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <p className="text-gray-600">{message}</p>
                        </CardContent>
                    </Card>
                </div>
            </SectionLayout>
        );
    }
    
    // Fallback for other sections
    return (
        <div className="min-h-screen bg-gray-50">
            <div className="max-w-4xl mx-auto px-6 py-8">
                <Card>
                    <CardHeader>
                        <CardTitle>Coming Soon</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <p className="text-gray-600">{message}</p>
                    </CardContent>
                </Card>
            </div>
        </div>
    );
}


