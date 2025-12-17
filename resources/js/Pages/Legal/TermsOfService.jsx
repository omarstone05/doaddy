import { Head } from '@inertiajs/react';

export default function TermsOfService() {
    return (
        <div className="min-h-screen bg-gray-50 py-12">
            <Head title="Terms of Service" />
            <div className="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
                <div className="bg-white shadow-lg rounded-lg p-8">
                    <h1 className="text-4xl font-bold text-gray-900 mb-2">Terms of Service</h1>
                    <p className="text-gray-600 mb-8">Last updated: {new Date().toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' })}</p>

                    <div className="prose prose-lg max-w-none">
                        <section className="mb-8">
                            <h2 className="text-2xl font-semibold text-gray-900 mb-4">1. Acceptance of Terms</h2>
                            <p className="text-gray-700 mb-4">
                                By accessing or using Addy ("the Service"), you agree to be bound by these Terms of Service ("Terms"). If you disagree with any part of these terms, you may not access the Service.
                            </p>
                        </section>

                        <section className="mb-8">
                            <h2 className="text-2xl font-semibold text-gray-900 mb-4">2. Description of Service</h2>
                            <p className="text-gray-700 mb-4">
                                Addy is a comprehensive business management platform that provides tools for financial management, sales tracking, inventory management, human resources, compliance, and decision-making. The Service is provided "as is" and may be modified, updated, or discontinued at any time.
                            </p>
                        </section>

                        <section className="mb-8">
                            <h2 className="text-2xl font-semibold text-gray-900 mb-4">3. User Accounts</h2>
                            <h3 className="text-xl font-semibold text-gray-800 mb-3">3.1 Account Creation</h3>
                            <p className="text-gray-700 mb-4">
                                To use the Service, you must create an account by providing accurate, current, and complete information. You are responsible for maintaining the confidentiality of your account credentials and for all activities that occur under your account.
                            </p>

                            <h3 className="text-xl font-semibold text-gray-800 mb-3">3.2 Account Security</h3>
                            <p className="text-gray-700 mb-4">
                                You agree to notify us immediately of any unauthorized use of your account or any other breach of security. We are not liable for any loss or damage arising from your failure to protect your account credentials.
                            </p>
                        </section>

                        <section className="mb-8">
                            <h2 className="text-2xl font-semibold text-gray-900 mb-4">4. Acceptable Use</h2>
                            <p className="text-gray-700 mb-4">You agree not to:</p>
                            <ul className="list-disc pl-6 text-gray-700 mb-4">
                                <li>Use the Service for any illegal purpose or in violation of any laws</li>
                                <li>Transmit any harmful code, viruses, or malicious software</li>
                                <li>Attempt to gain unauthorized access to the Service or related systems</li>
                                <li>Interfere with or disrupt the Service or servers</li>
                                <li>Use automated systems to access the Service without authorization</li>
                                <li>Impersonate any person or entity or misrepresent your affiliation</li>
                                <li>Violate the intellectual property rights of others</li>
                                <li>Collect or harvest information about other users</li>
                            </ul>
                        </section>

                        <section className="mb-8">
                            <h2 className="text-2xl font-semibold text-gray-900 mb-4">5. Subscription and Payment</h2>
                            <h3 className="text-xl font-semibold text-gray-800 mb-3">5.1 Subscription Plans</h3>
                            <p className="text-gray-700 mb-4">
                                The Service may be offered on a subscription basis. Subscription fees, billing cycles, and payment terms will be clearly disclosed at the time of subscription. All fees are non-refundable unless otherwise stated.
                            </p>

                            <h3 className="text-xl font-semibold text-gray-800 mb-3">5.2 Payment Terms</h3>
                            <p className="text-gray-700 mb-4">
                                You agree to pay all fees associated with your subscription. Failure to pay may result in suspension or termination of your account. We reserve the right to change our pricing with reasonable notice.
                            </p>

                            <h3 className="text-xl font-semibold text-gray-800 mb-3">5.3 Cancellation</h3>
                            <p className="text-gray-700 mb-4">
                                You may cancel your subscription at any time. Cancellation will take effect at the end of your current billing period. You will continue to have access to the Service until the end of your paid period.
                            </p>
                        </section>

                        <section className="mb-8">
                            <h2 className="text-2xl font-semibold text-gray-900 mb-4">6. User Content and Data</h2>
                            <h3 className="text-xl font-semibold text-gray-800 mb-3">6.1 Your Content</h3>
                            <p className="text-gray-700 mb-4">
                                You retain ownership of all data and content you upload or create using the Service ("User Content"). You grant us a license to use, store, and process your User Content solely for the purpose of providing and improving the Service.
                            </p>

                            <h3 className="text-xl font-semibold text-gray-800 mb-3">6.2 Data Backup</h3>
                            <p className="text-gray-700 mb-4">
                                While we implement backup and recovery procedures, you are responsible for maintaining your own backups of important data. We are not liable for any loss of data.
                            </p>
                        </section>

                        <section className="mb-8">
                            <h2 className="text-2xl font-semibold text-gray-900 mb-4">7. Intellectual Property</h2>
                            <p className="text-gray-700 mb-4">
                                The Service, including its original content, features, and functionality, is owned by Addy and is protected by international copyright, trademark, patent, trade secret, and other intellectual property laws. You may not reproduce, distribute, modify, or create derivative works of the Service without our express written permission.
                            </p>
                        </section>

                        <section className="mb-8">
                            <h2 className="text-2xl font-semibold text-gray-900 mb-4">8. Disclaimers and Limitations of Liability</h2>
                            <h3 className="text-xl font-semibold text-gray-800 mb-3">8.1 Service Availability</h3>
                            <p className="text-gray-700 mb-4">
                                We strive to provide reliable service but do not guarantee that the Service will be uninterrupted, secure, or error-free. The Service is provided "as is" without warranties of any kind, either express or implied.
                            </p>

                            <h3 className="text-xl font-semibold text-gray-800 mb-3">8.2 Limitation of Liability</h3>
                            <p className="text-gray-700 mb-4">
                                To the maximum extent permitted by law, Addy shall not be liable for any indirect, incidental, special, consequential, or punitive damages, or any loss of profits or revenues, whether incurred directly or indirectly, or any loss of data, use, goodwill, or other intangible losses resulting from your use of the Service.
                            </p>
                        </section>

                        <section className="mb-8">
                            <h2 className="text-2xl font-semibold text-gray-900 mb-4">9. Indemnification</h2>
                            <p className="text-gray-700 mb-4">
                                You agree to indemnify, defend, and hold harmless Addy and its officers, directors, employees, and agents from and against any claims, liabilities, damages, losses, and expenses, including reasonable attorneys' fees, arising out of or in any way connected with your use of the Service, your User Content, or your violation of these Terms.
                            </p>
                        </section>

                        <section className="mb-8">
                            <h2 className="text-2xl font-semibold text-gray-900 mb-4">10. Termination</h2>
                            <p className="text-gray-700 mb-4">
                                We may terminate or suspend your account and access to the Service immediately, without prior notice, for any reason, including if you breach these Terms. Upon termination, your right to use the Service will cease immediately. We may delete your account and data after a reasonable period following termination.
                            </p>
                        </section>

                        <section className="mb-8">
                            <h2 className="text-2xl font-semibold text-gray-900 mb-4">11. Governing Law</h2>
                            <p className="text-gray-700 mb-4">
                                These Terms shall be governed by and construed in accordance with the laws of [Your Jurisdiction], without regard to its conflict of law provisions. Any disputes arising under these Terms shall be subject to the exclusive jurisdiction of the courts in [Your Jurisdiction].
                            </p>
                        </section>

                        <section className="mb-8">
                            <h2 className="text-2xl font-semibold text-gray-900 mb-4">12. Changes to Terms</h2>
                            <p className="text-gray-700 mb-4">
                                We reserve the right to modify these Terms at any time. We will notify users of any material changes by posting the updated Terms on this page and updating the "Last updated" date. Your continued use of the Service after such modifications constitutes acceptance of the updated Terms.
                            </p>
                        </section>

                        <section className="mb-8">
                            <h2 className="text-2xl font-semibold text-gray-900 mb-4">13. Contact Information</h2>
                            <p className="text-gray-700 mb-4">
                                If you have any questions about these Terms of Service, please contact us at:
                            </p>
                            <div className="bg-gray-50 p-4 rounded-lg">
                                <p className="text-gray-700"><strong>Email:</strong> legal@addy.app</p>
                                <p className="text-gray-700"><strong>Address:</strong> [Your Business Address]</p>
                            </div>
                        </section>
                    </div>
                </div>
            </div>
        </div>
    );
}

