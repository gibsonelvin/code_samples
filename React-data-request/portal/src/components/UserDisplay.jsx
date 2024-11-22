import PropTypes from 'prop-types'
import BasicFieldItem from './BasicFieldItem'

export default function UserDisplay({ user }) {
	return (
		<>
			<BasicFieldItem label='Name' value={`${user.data.firstName} ${user.data.lastName}`} />
			<BasicFieldItem label='Email' value={user.data.email} />
			<BasicFieldItem label='Phone' value={user.data.phone} />
			<BasicFieldItem label='Country' value={user.data.country} />
			<BasicFieldItem label='State' value={user.data.state} />
			<BasicFieldItem label='City' value={user.data.city} />
			<BasicFieldItem label='Street' value={user.data.street} />
			<BasicFieldItem label='Zip Code' value={user.data.zipCode || ''} />
		</>
	)
}

UserDisplay.propTypes = {
	user: PropTypes.object.isRequired
}
