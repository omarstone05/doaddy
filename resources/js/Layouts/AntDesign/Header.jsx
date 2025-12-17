import React, { useState, useEffect } from 'react';
import {
  Row,
  Col,
  Breadcrumb,
  Badge,
  Dropdown,
  Button,
  List,
  Avatar,
  Input,
  Drawer,
  Typography,
  Switch,
} from 'antd';
import {
  SearchOutlined,
  BellOutlined,
  SettingOutlined,
  MenuOutlined,
} from '@ant-design/icons';
import { Link, router, usePage } from '@inertiajs/react';

const { Title, Text } = Typography;

const bell = [
  <svg
    width="20"
    height="20"
    viewBox="0 0 20 20"
    fill="none"
    xmlns="http://www.w3.org/2000/svg"
    key={0}
  >
    <path
      d="M10 2C6.68632 2 4.00003 4.68629 4.00003 8V11.5858L3.29292 12.2929C3.00692 12.5789 2.92137 13.009 3.07615 13.3827C3.23093 13.7564 3.59557 14 4.00003 14H16C16.4045 14 16.7691 13.7564 16.9239 13.3827C17.0787 13.009 16.9931 12.5789 16.7071 12.2929L16 11.5858V8C16 4.68629 13.3137 2 10 2Z"
      fill="#111827"
    ></path>
    <path
      d="M10 18C8.34315 18 7 16.6569 7 15H13C13 16.6569 11.6569 18 10 18Z"
      fill="#111827"
    ></path>
  </svg>,
];

const toggler = [
  <svg
    width="20"
    height="20"
    xmlns="http://www.w3.org/2000/svg"
    viewBox="0 0 448 512"
    key={0}
  >
    <path d="M16 132h416c8.837 0 16-7.163 16-16V76c0-8.837-7.163-16-16-16H16C7.163 60 0 67.163 0 76v40c0 8.837 7.163 16 16 16zm0 160h416c8.837 0 16-7.163 16-16v-40c0-8.837-7.163-16-16-16H16c-8.837 0-16 7.163-16 16v40c0 8.837 7.163 16 16 16zm0 160h416c8.837 0 16-7.163 16-16v-40c0-8.837-7.163-16-16-16H16c-8.837 0-16 7.163-16 16v40c0 8.837 7.163 16 16 16z"></path>
  </svg>,
];

const setting = [
  <svg
    width="20"
    height="20"
    viewBox="0 0 20 20"
    fill="none"
    xmlns="http://www.w3.org/2000/svg"
    key={0}
  >
    <path
      fillRule="evenodd"
      clipRule="evenodd"
      d="M11.4892 3.17094C11.1102 1.60969 8.8898 1.60969 8.51078 3.17094C8.26594 4.17949 7.11045 4.65811 6.22416 4.11809C4.85218 3.28212 3.28212 4.85218 4.11809 6.22416C4.65811 7.11045 4.17949 8.26593 3.17094 8.51078C1.60969 8.8898 1.60969 11.1102 3.17094 11.4892C4.17949 11.7341 4.65811 12.8896 4.11809 13.7758C3.28212 15.1478 4.85218 16.7179 6.22417 15.8819C7.11045 15.3419 8.26594 15.8205 8.51078 16.8291C8.8898 18.3903 11.1102 18.3903 11.4892 16.8291C11.7341 15.8205 12.8896 15.3419 13.7758 15.8819C15.1478 16.7179 16.7179 15.1478 15.8819 13.7758C15.3419 12.8896 15.8205 11.7341 16.8291 11.4892C18.3903 11.1102 18.3903 8.8898 16.8291 8.51078C15.8205 8.26593 15.3419 7.11045 15.8819 6.22416C16.7179 4.85218 15.1478 3.28212 13.7758 4.11809C12.8896 4.65811 11.7341 4.17949 11.4892 3.17094ZM10 13C11.6569 13 13 11.6569 13 10C13 8.34315 11.6569 7 10 7C8.34315 7 7 8.34315 7 10C7 11.6569 8.34315 13 10 13Z"
      fill="#111827"
    ></path>
  </svg>,
];

const profile = [
  <svg
    width="20"
    height="20"
    viewBox="0 0 20 20"
    fill="none"
    xmlns="http://www.w3.org/2000/svg"
    key={0}
  >
    <path
      fillRule="evenodd"
      clipRule="evenodd"
      d="M18 10C18 14.4183 14.4183 18 10 18C5.58172 18 2 14.4183 2 10C2 5.58172 5.58172 2 10 2C14.4183 2 18 5.58172 18 10ZM12 7C12 8.10457 11.1046 9 10 9C8.89543 9 8 8.10457 8 7C8 5.89543 8.89543 5 10 5C11.1046 5 12 5.89543 12 7ZM9.99993 11C7.98239 11 6.24394 12.195 5.45374 13.9157C6.55403 15.192 8.18265 16 9.99998 16C11.8173 16 13.4459 15.1921 14.5462 13.9158C13.756 12.195 12.0175 11 9.99993 11Z"
      fill="#111827"
    ></path>
  </svg>,
];

export default function Header({
  onPress,
  handleSidenavColor,
  handleSidenavType,
  handleFixedNavbar,
}) {
  const { auth, url } = usePage().props;
  const [visible, setVisible] = useState(false);
  const [sidenavType, setSidenavType] = useState('transparent');

  useEffect(() => window.scrollTo(0, 0));

  const showDrawer = () => setVisible(true);
  const hideDrawer = () => setVisible(false);

  const handleLogout = () => {
    router.post('/logout');
  };

  const pathname = url || (typeof window !== 'undefined' ? window.location.pathname : '');
  const pageName = pathname.replace('/', '').split('/')[0] || 'dashboard';

  const menu = (
    <List
      min-width="100%"
      className="header-notifications-dropdown"
      itemLayout="horizontal"
      dataSource={[]}
      renderItem={(item) => (
        <List.Item>
          <List.Item.Meta
            title="No notifications"
            description="You're all caught up!"
          />
        </List.Item>
      )}
    />
  );

  return (
    <>
      <div className="setting-drwer" onClick={showDrawer}>
        {setting}
      </div>
      <Row gutter={[24, 0]}>
        <Col span={24} md={6}>
          <Breadcrumb>
            <Breadcrumb.Item>
              <Link href="/dashboard">Pages</Link>
            </Breadcrumb.Item>
            <Breadcrumb.Item style={{ textTransform: 'capitalize' }}>
              {pageName}
            </Breadcrumb.Item>
          </Breadcrumb>
          <div className="ant-page-header-heading">
            <span
              className="ant-page-header-heading-title"
              style={{ textTransform: 'capitalize' }}
            >
              {pageName}
            </span>
          </div>
        </Col>
        <Col span={24} md={18} className="header-control">
          <Badge size="small" count={0}>
            <Dropdown overlay={menu} trigger={['click']}>
              <a
                href="#pablo"
                className="ant-dropdown-link"
                onClick={(e) => e.preventDefault()}
              >
                {bell}
              </a>
            </Dropdown>
          </Badge>
          <Button type="link" onClick={showDrawer}>
            {setting}
          </Button>
          <Button
            type="link"
            className="sidebar-toggler"
            onClick={() => onPress()}
          >
            {toggler}
          </Button>
          {auth?.user ? (
            <Dropdown
              overlay={
                <div style={{ background: '#fff', padding: '8px', borderRadius: '4px', boxShadow: '0 2px 8px rgba(0,0,0,0.15)' }}>
                  <Button type="link" onClick={handleLogout} style={{ width: '100%', textAlign: 'left' }}>
                    Logout
                  </Button>
                </div>
              }
              trigger={['click']}
            >
              <a href="#pablo" onClick={(e) => e.preventDefault()}>
                {profile}
                <span style={{ marginLeft: '8px' }}>{auth.user.name}</span>
              </a>
            </Dropdown>
          ) : (
            <Link href="/login" className="btn-sign-in">
              {profile}
              <span>Sign in</span>
            </Link>
          )}
          <Input
            className="header-search"
            placeholder="Type here..."
            prefix={<SearchOutlined />}
          />
        </Col>
      </Row>
      <Drawer
        className="settings-drawer"
        mask={true}
        width={360}
        onClose={hideDrawer}
        placement="right"
        open={visible}
      >
        <div layout="vertical">
          <div className="header-top">
            <Title level={4}>
              Configurator
              <Text className="subtitle">See our dashboard options.</Text>
            </Title>
          </div>

          <div className="sidebar-color">
            <Title level={5}>Sidebar Color</Title>
            <div className="theme-color mb-2">
              <Button
                type="primary"
                onClick={() => handleSidenavColor('#1890ff')}
                style={{ marginRight: '8px', marginBottom: '8px' }}
              >
                Blue
              </Button>
              <Button
                type="primary"
                danger={false}
                onClick={() => handleSidenavColor('#52c41a')}
                style={{ marginRight: '8px', marginBottom: '8px', background: '#52c41a', borderColor: '#52c41a' }}
              >
                Green
              </Button>
              <Button
                danger
                onClick={() => handleSidenavColor('#d9363e')}
                style={{ marginRight: '8px', marginBottom: '8px' }}
              >
                Red
              </Button>
              <Button
                onClick={() => handleSidenavColor('#fadb14')}
                style={{ marginRight: '8px', marginBottom: '8px', background: '#fadb14', borderColor: '#fadb14' }}
              >
                Yellow
              </Button>
              <Button
                onClick={() => handleSidenavColor('#111')}
                style={{ marginRight: '8px', marginBottom: '8px', background: '#111', borderColor: '#111', color: '#fff' }}
              >
                Black
              </Button>
            </div>

            <div className="sidebarnav-color mb-2">
              <Title level={5}>Sidenav Type</Title>
              <Text>Choose between 2 different sidenav types.</Text>
              <div style={{ marginTop: '8px' }}>
                <Button
                  type={sidenavType === 'transparent' ? 'primary' : 'default'}
                  onClick={() => {
                    handleSidenavType('transparent');
                    setSidenavType('transparent');
                  }}
                  style={{ marginRight: '8px' }}
                >
                  TRANSPARENT
                </Button>
                <Button
                  type={sidenavType === 'white' ? 'primary' : 'default'}
                  onClick={() => {
                    handleSidenavType('#fff');
                    setSidenavType('white');
                  }}
                >
                  WHITE
                </Button>
              </div>
            </div>
            <div className="fixed-nav mb-2">
              <Title level={5}>Navbar Fixed </Title>
              <Switch onChange={(e) => handleFixedNavbar(e)} />
            </div>
          </div>
        </div>
      </Drawer>
    </>
  );
}

