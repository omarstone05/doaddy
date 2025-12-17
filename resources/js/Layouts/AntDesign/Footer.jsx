import React from 'react';
import { Layout, Row, Col } from 'antd';
import { HeartFilled } from '@ant-design/icons';

const { Footer: AntFooter } = Layout;

export default function Footer() {
  return (
    <AntFooter style={{ background: '#fafafa' }}>
      <Row className="just">
        <Col xs={24} md={12} lg={12}>
          <div className="copyright">
            © {new Date().getFullYear()}, made with
            {<HeartFilled />} by
            <a href="#pablo" className="font-weight-bold" target="_blank" rel="noopener noreferrer">
              Addy
            </a>
            for a better web.
          </div>
        </Col>
        <Col xs={24} md={12} lg={12}>
          <div className="footer-menu">
            <ul>
              <li className="nav-item">
                <a
                  href="#pablo"
                  className="nav-link text-muted"
                  target="_blank"
                  rel="noopener noreferrer"
                >
                  About Us
                </a>
              </li>
              <li className="nav-item">
                <a
                  href="#pablo"
                  className="nav-link text-muted"
                  target="_blank"
                  rel="noopener noreferrer"
                >
                  Blog
                </a>
              </li>
              <li className="nav-item">
                <a
                  href="#pablo"
                  className="nav-link pe-0 text-muted"
                  target="_blank"
                  rel="noopener noreferrer"
                >
                  License
                </a>
              </li>
            </ul>
          </div>
        </Col>
      </Row>
    </AntFooter>
  );
}

