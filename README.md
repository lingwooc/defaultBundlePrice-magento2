# flatCategory-magento2
A flat category rest endpoint for magento2 to work round the stupidity that is the default tree interface. This just gives a list of all categories with searchCriteria supported to make caching and lookups easy, instead of hard and annoying.

# Installation
- Extract over your magento installation.
- php bin/magento deploy:mode:set developer
- php bin/magento setup:upgrade
- php bin/magento setup:di:compile
- php bin/magento deploy:mode:set production
- php bin/magento maintenance:disable

# Usage
GET rest/V1/flatCategories?searchCriteria
Response:
```json
{
  "items": [
    {
      "id": 0,
      "parent_id": 0,
      "name": "string",
      "is_active": true,
      "position": 0,
      "level": 0,
      "children": "string",
      "created_at": "string",
      "updated_at": "string",
      "path": "string",
      "available_sort_by": [
        "string"
      ],
      "include_in_menu": true,
      "extension_attributes": {},
      "custom_attributes": [
        {
          "attribute_code": "string",
          "value": "string"
        }
      ]
    }
  ],
  "search_criteria": {
    "filter_groups": [
      {
        "filters": [
          {
            "field": "string",
            "value": "string",
            "condition_type": "string"
          }
        ]
      }
    ],
    "sort_orders": [
      {
        "field": "string",
        "direction": "string"
      }
    ],
    "page_size": 0,
    "current_page": 0
  },
  "total_count": 0
}
```
