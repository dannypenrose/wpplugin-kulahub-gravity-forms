# KulaHub Gravity Forms Integration - Developer Documentation

## Hooks and Filters

### Filters

#### `kulahub_gf_form_data`
Filter the form data before it's sent to KulaHub. 

## Field mapping

Each Gravity Forms field carries a KulaHub Field ID (the `encryptField` setting on
the field). Fields whose ID matches a contact field are nested under `Contact` in
the payload; everything else is sent at the top level alongside `formTypeId` and
`clientId`.

Contact fields: `title`, `firstname`, `lastname`, `jobtitle`, `website`,
`address1`, `address2`, `town`, `county`, `postcode`, `country`, `telephone`,
`mobile`, `email`, `emailsubscribe`, `organisationname`.

Custom fields use the form's personalisation code without the hashtags, for
example `varchar1`, `money1`, `number1`.

### emailsubscribe is a Boolean

The KulaHub `AddForm` endpoint types `Contact.Emailsubscribe` as a Boolean, so the
plugin converts the submitted value rather than passing the text straight through:

| Field type | Rule |
| --- | --- |
| Checkbox or consent | Ticked is `true`, unticked is `false` |
| Radio, select or text | The value is interpreted: `Yes`, `True`, `1`, `On` and `Opt in` give `true`; `No`, `No thanks`, `False`, `0`, `Off`, `Opt out` and `Unsubscribe` give `false`; an empty answer is `false` |

The resulting value is sent as a JSON Boolean (`"emailsubscribe": true`), never as
a string such as `"True"`.
