import React from 'react';
import { Menu } from 'antd';
import { Link, usePage } from '@inertiajs/react';
import { navigation } from '../navigation';

// Convert lucide-react icons to SVG elements for Ant Design
const createIcon = (IconComponent, color) => {
  return <IconComponent size={20} style={{ color }} />;
};

export default function Sidenav({ color = '#1890ff' }) {
  const { url } = usePage().props;
  const currentPath = url || (typeof window !== 'undefined' ? window.location.pathname : '');
  
  // Build menu items for Ant Design v5 Menu component
  const menuItems = [];
  let keyCounter = 1;
  
  navigation.forEach((section) => {
    // Add section items
    section.items.forEach((item) => {
      const isActive = currentPath === item.href || currentPath.startsWith(item.href + '/');
      menuItems.push({
        key: item.href,
        icon: (
          <span
            className="icon"
            style={{
              background: isActive ? color : '',
              display: 'inline-flex',
              alignItems: 'center',
              justifyContent: 'center',
              width: '20px',
              height: '20px',
              borderRadius: '4px',
            }}
          >
            {createIcon(item.icon, isActive ? '#fff' : color)}
          </span>
        ),
        label: <Link href={item.href}>{item.name}</Link>,
      });
    });
  });
  
  const selectedKeys = menuItems
    .filter(item => currentPath === item.key || currentPath.startsWith(item.key + '/'))
    .map(item => item.key);
  
  return (
    <>
      <div className="brand">
        <img src="/images/logo.png" alt="Addy" />
        <span>Addy Dashboard</span>
      </div>
      <hr />
      <Menu 
        theme="light" 
        mode="inline" 
        items={menuItems}
        selectedKeys={selectedKeys}
      />
      <div className="aside-footer">
        <div
          className="footer-box"
          style={{
            background: color,
          }}
        >
          <span className="icon" style={{ color: '#fff' }}>
            <svg
              width="20"
              height="20"
              viewBox="0 0 20 20"
              xmlns="http://www.w3.org/2000/svg"
            >
              <path
                d="M3 4C3 3.44772 3.44772 3 4 3H16C16.5523 3 17 3.44772 17 4V6C17 6.55228 16.5523 7 16 7H4C3.44772 7 3 6.55228 3 6V4Z"
                fill="#fff"
              ></path>
              <path
                d="M3 10C3 9.44771 3.44772 9 4 9H10C10.5523 9 11 9.44771 11 10V16C11 16.5523 10.5523 17 10 17H4C3.44772 17 3 16.5523 3 16V10Z"
                fill="#fff"
              ></path>
              <path
                d="M14 9C13.4477 9 13 9.44771 13 10V16C13 16.5523 13.4477 17 14 17H16C16.5523 17 17 16.5523 17 16V10C17 9.44771 16.5523 9 16 9H14Z"
                fill="#fff"
              ></path>
            </svg>
          </span>
          <h6>Need Help?</h6>
          <p>Please check our docs</p>
          <button type="button" className="ant-btn ant-btn-sm ant-btn-block ant-btn-primary" style={{ marginTop: '8px' }}>
            DOCUMENTATION
          </button>
        </div>
      </div>
    </>
  );
}

