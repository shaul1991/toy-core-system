import { Metadata } from 'next';

export const metadata: Metadata = {
  title: '마이페이지',
  description: '계정 정보 및 설정을 관리하세요.',
};

export default function MyPageLayout({
  children,
}: {
  children: React.ReactNode;
}) {
  return children;
}
