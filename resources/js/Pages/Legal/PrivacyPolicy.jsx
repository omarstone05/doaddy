import { Head } from '@inertiajs/react';

export default function PrivacyPolicy() {
    return (
        <div className="min-h-screen bg-gray-50 py-12">
            <Head title="Privacy Policy" />
            <div className="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
                <div className="bg-white shadow-lg rounded-lg p-8">
                    <h1 className="text-4xl font-bold text-gray-900 mb-2">Privacy Policy</h1>
                    <p className="text-gray-600 mb-8">Last updated: {new Date().toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' })}</p>

                    <div className="prose prose-lg max-w-none">
                        <section className="mb-8">
                            <h2 className="text-2xl font-semibold text-gray-900 mb-4">1. Introduction</h2>
                            <p className="text-gray-700 mb-4">
                                Welcome to Addy ("we," "our," or "us"). We are committed to protecting your personal information and your right to privacy. This Privacy Policy explains how we collect, use, disclose, and safeguard your information when you use our business management platform.
                            </p>
                        </section>

                        <section className="mb-8">
                            <h2 className="text-2xl font-semibold text-gray-900 mb-4">2. Information We Collect</h2>
                            <h3 className="text-xl font-semibold text-gray-800 mb-3">2.1 Information You Provide</h3>
                            <ul className="list-disc pl-6 text-gray-700 mb-4">
                                <li>Account information (name, email address, phone number)</li>
                                <li>Business information (company name, industry, business description)</li>
                                <li>Financial data (transactions, invoices, expenses, budgets)</li>
                                <li>Customer and vendor information</li>
                                <li>Employee and team member data</li>
                                <li>Inventory and product information</li>
                                <li>Documents and files you upload</li>
                            </ul>

                            <h3 className="text-xl font-semibold text-gray-800 mb-3">2.2 Automatically Collected Information</h3>
                            <ul className="list-disc pl-6 text-gray-700 mb-4">
                                <li>Usage data and analytics</li>
                                <li>Device information</li>
                                <li>IP address and location data</li>
                                <li>Cookies and tracking technologies</li>
                            </ul>
                        </section>

                        <section className="mb-8">
                            <h2 className="text-2xl font-semibold text-gray-900 mb-4">3. How We Use Your Information</h2>
                            <p className="text-gray-700 mb-4">We use the information we collect to:</p>
                            <ul className="list-disc pl-6 text-gray-700 mb-4">
                                <li>Provide, maintain, and improve our services</li>
                                <li>Process transactions and manage your account</li>
                                <li>Send you important updates and notifications</li>
                                <li>Respond to your inquiries and provide customer support</li>
                                <li>Detect and prevent fraud or abuse</li>
                                <li>Comply with legal obligations</li>
                                <li>Analyze usage patterns to enhance user experience</li>
                            </ul>
                        </section>

                        <section className="mb-8">
                            <h2 className="text-2xl font-semibold text-gray-900 mb-4">4. Data Storage and Security</h2>
                            <p className="text-gray-700 mb-4">
                                We implement appropriate technical and organizational security measures to protect your personal information. Your data is stored securely using industry-standard encryption and access controls. However, no method of transmission over the internet is 100% secure, and we cannot guarantee absolute security.
                            </p>
                        </section>

                        <section className="mb-8">
                            <h2 className="text-2xl font-semibold text-gray-900 mb-4">5. Data Sharing and Disclosure</h2>
                            <p className="text-gray-700 mb-4">We do not sell your personal information. We may share your information only in the following circumstances:</p>
                            <ul className="list-disc pl-6 text-gray-700 mb-4">
                                <li>With your explicit consent</li>
                                <li>To comply with legal obligations or court orders</li>
                                <li>To protect our rights, privacy, safety, or property</li>
                                <li>With service providers who assist in operating our platform (under strict confidentiality agreements)</li>
                                <li>In connection with a business transfer or merger</li>
                            </ul>
                        </section>

                        <section className="mb-8">
                            <h2 className="text-2xl font-semibold text-gray-900 mb-4">6. Third-Party Services</h2>
                            <p className="text-gray-700 mb-4">
                                Our platform may integrate with third-party services (such as payment processors, cloud storage providers, or accounting software). These services have their own privacy policies, and we encourage you to review them. We are not responsible for the privacy practices of third-party services.
                            </p>
                        </section>

                        <section className="mb-8">
                            <h2 className="text-2xl font-semibold text-gray-900 mb-4">7. Your Rights</h2>
                            <p className="text-gray-700 mb-4">You have the right to:</p>
                            <ul className="list-disc pl-6 text-gray-700 mb-4">
                                <li>Access and receive a copy of your personal data</li>
                                <li>Rectify inaccurate or incomplete information</li>
                                <li>Request deletion of your personal data</li>
                                <li>Object to processing of your personal data</li>
                                <li>Request restriction of processing</li>
                                <li>Data portability</li>
                                <li>Withdraw consent at any time</li>
                            </ul>
                        </section>

                        <section className="mb-8">
                            <h2 className="text-2xl font-semibold text-gray-900 mb-4">8. Data Retention</h2>
                            <p className="text-gray-700 mb-4">
                                We retain your personal information for as long as necessary to fulfill the purposes outlined in this Privacy Policy, unless a longer retention period is required or permitted by law. When you delete your account, we will delete or anonymize your personal information, subject to legal retention requirements.
                            </p>
                        </section>

                        <section className="mb-8">
                            <h2 className="text-2xl font-semibold text-gray-900 mb-4">9. Children's Privacy</h2>
                            <p className="text-gray-700 mb-4">
                                Our services are not intended for individuals under the age of 18. We do not knowingly collect personal information from children. If you become aware that a child has provided us with personal information, please contact us immediately.
                            </p>
                        </section>

                        <section className="mb-8">
                            <h2 className="text-2xl font-semibold text-gray-900 mb-4">10. Changes to This Privacy Policy</h2>
                            <p className="text-gray-700 mb-4">
                                We may update this Privacy Policy from time to time. We will notify you of any changes by posting the new Privacy Policy on this page and updating the "Last updated" date. You are advised to review this Privacy Policy periodically for any changes.
                            </p>
                        </section>

                        <section className="mb-8">
                            <h2 className="text-2xl font-semibold text-gray-900 mb-4">11. Contact Us</h2>
                            <p className="text-gray-700 mb-4">
                                If you have any questions about this Privacy Policy or our data practices, please contact us at:
                            </p>
                            <div className="bg-gray-50 p-4 rounded-lg">
                                <p className="text-gray-700"><strong>Email:</strong> privacy@addy.app</p>
                                <p className="text-gray-700"><strong>Address:</strong> [Your Business Address]</p>
                            </div>
                        </section>
                    </div>
                </div>
            </div>
        </div>
    );
}

