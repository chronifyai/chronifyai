# Privacy & Data Handling - ChronifyAI Plugin

## Overview

The ChronifyAI plugin transmits course data from your Moodle installation to external servers for archiving and compliance purposes. This document details what data is transmitted, when, and how.

**⚠️ IMPORTANT:** This plugin sends data to external servers. Read this document completely before installation.

---

## Data Transmitted to External Servers

### 1. Course Backups

When you initiate a course backup, the following data MAY be transmitted (depending on backup settings):

#### Always Included:
- **Course Information:**
  - Course ID, name, shortname, description
  - Course category and structure
  - Section organization and names
  - Course settings and configurations
  
- **Activity Configurations:**
  - Quiz questions and settings
  - Assignment instructions and rubrics
  - Forum topics and structure (not posts unless user data included)
  - Activity settings and parameters
  
- **Course Resources:**
  - Files uploaded by teachers
  - Course documents and media
  - Resource descriptions and settings

#### Optionally Included (when "Include users" is selected):
- **User Identity:**
  - Full names (first name, last name)
  - Usernames
  - Email addresses
  - User profile information
  - User IDs (Moodle internal)
  
- **Student Work:**
  - Assignment submissions (text and files)
  - Quiz attempts with answers
  - Forum posts and discussions
  - Workshop submissions and assessments
  - Lesson attempts and responses
  
- **Academic Records:**
  - Grades for all activities
  - Feedback comments from teachers
  - Grading rubric scores
  - Grade history and modifications
  
- **Progress Data:**
  - Activity completion status
  - Course completion records
  - Time spent on activities
  - Last access dates
  
- **Enrollment Data:**
  - Role assignments (student, teacher, etc.)
  - Enrollment dates
  - Group memberships
  - Cohort associations

### 2. Course Reports

When generating course reports:
- Course metadata (name, dates, structure)
- Aggregated statistics (enrollment numbers, completion rates)
- Activity usage statistics
- **May include:** Student names and IDs in detailed reports

### 3. Transcript Exports

When exporting transcripts:
- **Student Identity:** Full names, usernames, email addresses
- **Academic History:** Complete grade history across all courses
- **Completion Records:** Course completion dates and status
- **Achievements:** Certificates and badges earned
- **Enrollment History:** All course enrollments with dates

### 4. API Authentication

- Your Moodle site URL
- API credentials (Client ID and Secret - encrypted)
- Timestamp of API requests
- User agent information

---

## Where Does Data Go?

### Default Configuration:
- **Destination:** ChronifyAI commercial service
- **API Endpoint:** https://api.chronifyai.com
- **Dashboard:** https://app.chronifyai.com
- **Data Location:** [To be specified by ChronifyAI - e.g., "US-based AWS servers"]
- **Data Processing Agreement:** Required for GDPR compliance

### Self-Hosted Configuration:
- You can configure your own API endpoint
- Data stays within your control
- See API.md for self-hosting documentation

---

## Legal & Compliance Considerations

### GDPR (European Union)

**Status:** ✅ Plugin implements required Privacy API

**Actions Required:**
1. ✅ Execute Data Processing Agreement (DPA) with ChronifyAI
2. ✅ Update your privacy policy to mention external archiving
3. ✅ Document legal basis for data transfer:
   - Legitimate interest (preferred for archiving)
   - Contract (if ChronifyAI service is essential)
   - Consent (if obtained from users)
4. ✅ Inform users via privacy notices
5. ✅ Implement data subject rights procedures
6. ✅ Maintain records of processing activities (Art. 30)

**Legal Basis Examples:**
- "Legitimate interest in maintaining compliance records"
- "Contractual necessity for regulatory compliance"
- "Legal obligation to maintain education records"

### FERPA (United States)

**Status:** ⚠️ Student education records are transmitted externally

**Actions Required:**
1. ✅ Designate ChronifyAI as "school official" (34 CFR § 99.31(a)(1))
2. ✅ Execute written agreement documenting:
   - Legitimate educational interest
   - Use limitations
   - Access restrictions
   - Re-disclosure prohibitions
3. ✅ Maintain records of disclosures (34 CFR § 99.32)
4. ✅ Ensure direct control over outsourced services
5. ✅ Include in annual FERPA notification to students

**Required Agreement Terms:**
- ChronifyAI performs institutional service/function
- Access limited to legitimate educational interest
- Data not re-disclosed without authorization
- Subject to FERPA requirements

### Other Regulations

**Canada (PIPEDA):**
- Consent may be required for data transfer
- Document reasonable purposes for collection
- Ensure adequate protection in receiving country

**Australia (Privacy Act 1988):**
- Overseas disclosure provisions apply (APP 8)
- Reasonable steps to ensure compliance
- Include in privacy policy

**UK GDPR:**
- Similar to EU GDPR requirements
- International data transfer rules apply
- ICO registration may be required

---

## Administrator Responsibilities

### Before Enabling:

- [ ] Read this privacy document completely
- [ ] Consult with your legal/compliance team
- [ ] Review institutional privacy policy
- [ ] Execute data processing agreements with ChronifyAI
- [ ] Document legal basis for data processing
- [ ] Determine if you need parental consent (for minors)
- [ ] Update privacy notices for students/teachers
- [ ] Configure backup settings appropriately
- [ ] Train staff on data privacy implications

### After Enabling:

- [ ] Monitor data transmissions via Moodle logs
- [ ] Review backup settings regularly
- [ ] Respond to data subject requests appropriately
- [ ] Maintain audit trail of data transmissions
- [ ] Annual privacy compliance review
- [ ] Update documentation as regulations change
- [ ] Communicate with stakeholders about archiving

---

## User Rights (GDPR)

Users have the right to:

1. **Access (Art. 15):** Know what data is archived
2. **Rectification (Art. 16):** Correct inaccurate data
3. **Erasure (Art. 17):** Request deletion ("right to be forgotten")
4. **Portability (Art. 20):** Receive data in machine-readable format
5. **Object (Art. 21):** Object to processing
6. **Restriction (Art. 18):** Limit processing in certain circumstances

### How to Handle Requests:

1. **For Moodle data:**
   - Use Moodle's built-in privacy tools
   - Export/delete via Data Privacy admin interface
   
2. **For ChronifyAI archived data:**
   - Contact ChronifyAI support: support@chronifyai.com
   - Provide user identifiers and request details
   - ChronifyAI responds within required timeframes
   
3. **Documentation:**
   - Log all requests received
   - Document actions taken
   - Maintain response records
   - GDPR: Respond within 30 days (can extend to 90 days)

---

## Data Security

### In Transit:
- ✅ All transmissions use HTTPS/TLS 1.2+ encryption
- ✅ OAuth 2.0 authentication with bearer tokens
- ✅ API credentials stored encrypted in Moodle database
- ✅ Certificate validation enforced
- ✅ No plain-text transmission of sensitive data

### At Rest (ChronifyAI Servers):
- Encryption at rest (AES-256 standard)
- Access controls and role-based permissions
- Regular security audits and penetration testing
- SOC 2 Type 2 compliance [if applicable]
- ISO 27001 certification [if applicable]
- See ChronifyAI security documentation for details

### Access Controls:
- Multi-factor authentication required
- Role-based access control (RBAC)
- Audit logging of all access
- Regular access reviews
- Least privilege principle enforced

---

## Data Retention

### Moodle Side:
- **Backup files:** Deleted immediately after successful upload
- **API tokens:** Cached temporarily (24 hours maximum)
- **Logs:** Retained per Moodle configuration (typically 1 year)
- **Configuration:** Stored until plugin uninstalled

### ChronifyAI Side:
- **Default retention:** 7 years (configurable)
- **Minimum retention:** As required by regulations
- **Maximum retention:** Configurable per institution
- **Automatic deletion:** After retention period expires
- **Manual deletion:** Available on request
- **Deletion verification:** Provided upon request

### Retention Policies:
- Course backups: 7 years default (regulatory compliance)
- Transcripts: Permanent or per institutional policy
- Reports: 3 years default
- Audit logs: 7 years (for compliance)

---

## Opt-Out & Data Minimization

### Administrators Can:
- ✅ Choose to backup courses WITHOUT user data
- ✅ Exclude specific activities from backups
- ✅ Disable transcript exports entirely
- ✅ Configure custom retention periods
- ✅ Request deletion of archived data
- ✅ Revoke API access at any time

### Best Practices:
- ✅ **Data Minimization:** Only include user data when necessary
- ✅ **Purpose Limitation:** Archive only for compliance purposes
- ✅ **Retention Limits:** Use shortest period needed
- ✅ **Regular Review:** Audit archived content quarterly
- ✅ **Clean-up:** Delete unnecessary archives
- ✅ **Documentation:** Maintain records of decisions

### Course-Level Controls:
- Backup configuration stored per-course
- Teachers can request exclusion of their courses
- Sensitive courses can be excluded entirely
- Option to backup structure only (no user data)

---

## Incident Response

### Data Breach Notification:

If ChronifyAI experiences a data breach:

1. **ChronifyAI Actions:**
   - Notifies customers within 72 hours
   - Provides breach details and impact assessment
   - Documents remediation steps taken
   
2. **Your Actions:**
   - Assess impact on your users
   - Notify data protection authority if high risk (GDPR: 72 hours)
   - Notify affected users if high risk to rights/freedoms
   - Document breach in records
   - Review and update security measures

3. **Required Information:**
   - Nature of breach (confidentiality, integrity, availability)
   - Categories of data affected
   - Approximate number of records
   - Likely consequences
   - Measures taken to address breach

### Contact Information:
- **Security Issues:** security@chronifyai.com
- **Privacy Concerns:** privacy@chronifyai.com
- **General Support:** support@chronifyai.com
- **Emergency (24/7):** [If available]

---

## Audit & Compliance

### Available Logs:

**Moodle Logs:**
- All backup operations with timestamps
- User who initiated operation
- Whether user data was included
- Success/failure status
- API call attempts and responses

**ChronifyAI Logs:**
- Data received timestamps
- Data accessed by users
- Data modifications
- Data deletions
- Authentication events

### Audit Reports:

Request from ChronifyAI:
- SOC 2 Type 2 reports (if available)
- Data processing records (GDPR Art. 30)
- Penetration test results (summary)
- Compliance certifications
- Annual audit reports

### Recommended Audit Schedule:
- **Quarterly:** Review active backups and retention
- **Semi-Annual:** User access review
- **Annual:** Full compliance audit
- **Ad-hoc:** After any security incident

---

## Parent/Guardian Consent (Minors)

### When Required:
- Students under 13 (COPPA - US)
- Students under 16 (GDPR - EU, can be lowered to 13 by member states)
- Check local regulations for age of consent

### How to Obtain:
1. Update enrollment forms with consent language
2. Provide clear explanation of data use
3. Obtain verifiable parental consent
4. Maintain consent records
5. Allow withdrawal of consent

### Consent Language Example:
> "Our institution uses ChronifyAI to archive course content and student records for compliance purposes. This service may process your child's name, grades, and course work. By enrolling, you consent to this processing. You may withdraw consent by contacting [privacy officer]."

---

## Questions & Support

### Technical Questions:
- **Documentation:** See README.md and API.md
- **Email:** support@chronifyai.com
- **Installation Issues:** Moodle forums or ChronifyAI support

### Legal/Privacy Questions:
- **Your Institution:** Consult your legal counsel
- **ChronifyAI:** privacy@chronifyai.com
- **Moodle Community:** docs.moodle.org/en/Privacy

### Data Subject Requests:
- **Email:** privacy@chronifyai.com
- **Subject Line:** "Data Subject Request - [Institution Name]"
- **Include:** User identifiers, request type, institution details

---

## Document Updates

This privacy document should be reviewed:
- ✅ When plugin is updated
- ✅ When regulations change
- ✅ At least annually
- ✅ After any security incident
- ✅ When data processing changes

**Document Version:** 1.0  
**Last Updated:** December 17, 2025  
**Plugin Version:** 1.0.0  
**Maintained By:** ChronifyAI / SEBALE Innovations  
**Next Review:** December 17, 2026

---

## Compliance Checklist

Use this checklist before going live:

### Legal & Regulatory
- [ ] Legal team consulted
- [ ] Data Processing Agreement executed
- [ ] Privacy policy updated
- [ ] Legal basis documented
- [ ] FERPA compliance verified (if US)
- [ ] GDPR compliance verified (if EU)
- [ ] Local regulations reviewed

### Technical
- [ ] Plugin installed and tested
- [ ] API connection verified
- [ ] Backup settings configured
- [ ] Logging enabled and tested
- [ ] Security review completed
- [ ] Access controls configured

### Documentation
- [ ] Staff trained on privacy implications
- [ ] User notices updated
- [ ] Consent process implemented (if needed)
- [ ] Data subject request process documented
- [ ] Incident response plan updated

### Ongoing
- [ ] Regular audit schedule established
- [ ] Privacy officer assigned
- [ ] Monitoring procedures in place
- [ ] Annual review scheduled

---

## Disclaimer

**IMPORTANT:** This document is for informational purposes only. It does not constitute legal advice. Consult with qualified legal counsel regarding compliance with applicable data protection laws in your jurisdiction.

Each institution's legal requirements may differ based on:
- Jurisdiction and governing laws
- Type of institution (K-12, higher ed, corporate)
- Student/user population characteristics
- Existing privacy policies and commitments
- Specific contractual obligations

---

## Additional Resources

### Moodle Documentation:
- Privacy API: https://docs.moodle.org/dev/Privacy_API
- Data Privacy Tool: https://docs.moodle.org/en/Data_privacy
- GDPR Compliance: https://docs.moodle.org/en/GDPR

### Regulations:
- GDPR Full Text: https://gdpr-info.eu/
- FERPA Regulations: https://www2.ed.gov/policy/gen/guid/fpco/ferpa/
- COPPA: https://www.ftc.gov/enforcement/rules/rulemaking-regulatory-reform-proceedings/childrens-online-privacy-protection-rule

### ChronifyAI:
- Website: https://chronifyai.com
- Privacy Policy: https://chronifyai.com/privacy
- Terms of Service: https://chronifyai.com/terms
- Security: https://chronifyai.com/security

---

**For the most current privacy information, always check:**
- This PRIVACY.md file in your plugin installation
- ChronifyAI's privacy policy at their website
- Your institution's privacy officer or legal counsel

**Last Updated:** December 17, 2025
